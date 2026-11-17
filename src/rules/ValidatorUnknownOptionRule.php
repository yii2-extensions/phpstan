<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\rules;

use Closure;
use PhpParser\Node;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\{DependencyTracker, Scope};
use PHPStan\Reflection\{ClassReflection, ReflectionProvider};
use PHPStan\Rules\{IdentifierRuleError, Rule, RuleErrorBuilder};
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\{MixedType, ObjectType, Type};
use yii\base\Component;
use yii2\extensions\phpstan\exception\Message;
use yii\validators\{InlineValidator, Validator};
use yii2\extensions\phpstan\ServiceMap;

use function array_keys;
use function array_map;
use function array_merge;
use function array_slice;
use function in_array;
use function is_array;
use function is_int;
use function is_string;
use function str_contains;
use function strval;

/**
 * Reports unknown options in model validation rules using closures, qualified classes or Yii's declared built-in aliases.
 *
 * Assumes the declared alias table has not been changed at runtime. Method names and uncertain dispatches stand aside.
 * Configuration resolution and assignment semantics follow the shared object option rules.
 *
 * @implements Rule<Return_>
 */
final readonly class ValidatorUnknownOptionRule implements Rule
{
    /**
     * Resolves the class and configuration Yii constructs.
     */
    private readonly CreateObjectConfigurationResolver $resolver;

    /**
     * Initializes static class reflection and the analysis service map.
     */
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
        private readonly ServiceMap $serviceMap,
    ) {
        $this->resolver = new CreateObjectConfigurationResolver($reflectionProvider, $serviceMap);
    }

    /**
     * Returns the node class this rule analyses.
     *
     * @return class-string<Return_>
     */
    public function getNodeType(): string
    {
        return Return_::class;
    }

    /**
     * Returns diagnostics for unknown options explicitly written in model rules.
     *
     * @param Return_ $node Return statement to inspect.
     * @param DependencyTracker&Scope $scope Current PHPStan analysis scope.
     *
     * @return list<IdentifierRuleError> Errors at each rule's line.
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $owner = ModelRulesReader::owner($node, $scope);

        if ($owner === null || $node->expr === null) {
            return [];
        }

        $errors = [];
        $modelRulesReader = ModelRulesReader::entries($node->expr, $node->getStartLine(), $scope) ?? [];

        foreach ($modelRulesReader as [$index, $line, $rule]) {
            foreach ($this->unknown($owner, $rule, $line, $scope) as [$class, $option]) {
                $errors[] = RuleErrorBuilder::message(
                    Message::VALIDATOR_UNKNOWN_OPTION->getMessage(
                        is_int($index) ? "#{$index}" : "'{$index}'",
                        $owner->getDisplayName(),
                        $class,
                        $option,
                    ),
                )->identifier('yii2.validator.unknownOption')->line($line)->build();
            }
        }

        return $errors;
    }

    /**
     * @param DependencyTracker&Scope $scope Current analysis scope.
     *
     * @return list<array{string, string}> Created class and option name of each unknown option written in the rule.
     */
    private function unknown(ClassReflection $owner, Type $rule, int $line, Scope $scope): array
    {
        $values = ModelRulesReader::ruleValues($rule);

        // incomplete rules belong to yii2.validator.incompleteRule
        if ($values === null || isset($values[0], $values[1]) === false || $values[0]->isNull()->no() === false) {
            return [];
        }

        $type = $values[1];

        $inline = (new ObjectType(Closure::class))->isSuperTypeOf($type)->yes();

        $name = $inline ? null : ObjectConfigurationReader::constantString($type);

        $alias = null;

        if ($inline === false) {
            $scope->trackClassDependency(Validator::class);
            // Read declaration defaults, never the mutable runtime table configured by a consumer bootstrap.
            $validator = $this->reflectionProvider->getClass(Validator::class);

            $file = $validator->getFileName();

            if ($file !== null) {
                // Class signature dependencies do not include property initializer values.
                $scope->trackFileDependency($file);
            }

            $defaults = $validator->getNativeReflection()->getDefaultProperties();

            $aliases = $defaults['builtInValidators'] ?? null;

            $alias = $name !== null && is_array($aliases) ? ($aliases[$name] ?? null) : null;

            // Unknown unqualified names may be model or behavior methods, or aliases added at runtime.
            if ($name === null || ($alias === null && str_contains($name, '\\') === false)) {
                return [];
            }

            // Built-in aliases take priority over model methods, including overridden hasMethod().
            if (
                $alias === null && (
                    $owner->hasNativeMethod('hasMethod') === false
                    || $owner->getNativeMethod('hasMethod')
                        ->getDeclaringClass()
                        ->getName() !== Component::class
                )
            ) {
                return [];
            }
        }

        // createValidator(): array_slice($rule, 2) by position, then the keys it sets, on a PHP array of types
        $params = array_slice($values, 2);
        $written = array_map(strval(...), array_keys($params));

        $params['attributes'] = $values[0]->toArray();

        if ($inline) {
            $params['class'] = new ConstantStringType(InlineValidator::class);
            $params['method'] = $type;
        } else {
            unset($params['current']);
            if (is_array($alias)) {
                $aliasOptions = [];

                // Only a class value selects the validator; other defaults are neither written nor reported.
                foreach ($alias as $key => $value) {
                    $aliasOptions[$key] = is_string($value) ? new ConstantStringType($value) : new MixedType();
                }

                $params = array_merge($aliasOptions, $params);
            } else {
                $params['class'] = new ConstantStringType(is_string($alias) ? $alias : $name);
            }
        }

        $options = [];

        foreach ($params as $key => $value) {
            $options[] = [(string) $key, $line, $value];
        }

        $configuration = $this->resolver->resolveTypeOptions('Yii', $options, $line, $scope);

        if ($configuration === null || $configuration->classReflection->is(Validator::class) === false) {
            return [];
        }

        $unknown = [];

        foreach (
            ObjectOptionAssignment::unknownOptions(
                $configuration->classReflection,
                $configuration->options,
                $this->serviceMap,
                $scope,
            ) as [$option]
        ) {
            if (in_array($option, $written, true)) {
                $unknown[] = [$configuration->classReflection->getDisplayName(), $option];
            }
        }

        return $unknown;
    }
}
