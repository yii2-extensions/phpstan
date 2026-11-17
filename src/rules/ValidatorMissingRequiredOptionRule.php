<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\rules;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\{DependencyTracker, Scope};
use PHPStan\Reflection\{ClassReflection, ReflectionProvider};
use PHPStan\Rules\{IdentifierRuleError, Rule, RuleErrorBuilder};
use PHPStan\Type\Type;
use yii\validators\{FilterValidator, RangeValidator, RegularExpressionValidator};
use yii2\extensions\phpstan\exception\Message;
use yii2\extensions\phpstan\ServiceMap;

use function array_key_exists;
use function in_array;
use function is_int;
use function str_starts_with;

/**
 * Reports model validation rules that create {@see RangeValidator}, {@see RegularExpressionValidator} or
 * {@see FilterValidator} without the `range`, `pattern` or `filter` option, whose absence or `null` value makes the
 * validator's `init()` throw an `InvalidConfigException`.
 *
 * Only these exact classes are checked; subclasses stand aside. A container definition that sets the option, an option
 * whose type may be non-`null`, and any other option that isn't an `on ...` event or a plain public property, such as
 * an `as ...` behavior or an unknown name that Yii rejects first, make the rule stand aside.
 *
 * {@see ValidatorConfigurationResolver} for the dispatch, including the declared alias table assumption.
 *
 * @implements Rule<Return_>
 */
final readonly class ValidatorMissingRequiredOptionRule implements Rule
{
    /**
     * Prefix of the options that `Component::__set()` attaches as event handlers.
     */
    private const string EVENT_PREFIX = 'on ';
    /**
     * Error identifier reported by this rule.
     */
    private const string IDENTIFIER = 'yii2.validator.missingRequiredOption';
    /**
     * Option that `init()` requires, by exact validator class.
     */
    private const array REQUIRED_OPTIONS = [
        FilterValidator::class => 'filter',
        RangeValidator::class => 'range',
        RegularExpressionValidator::class => 'pattern',
    ];

    /**
     * Resolves the validator class and configuration Yii constructs.
     */
    private ValidatorConfigurationResolver $resolver;

    /**
     * Initializes static class reflection and the analysis service map.
     */
    public function __construct(ReflectionProvider $reflectionProvider, private ServiceMap $serviceMap)
    {
        $this->resolver = new ValidatorConfigurationResolver($reflectionProvider, $serviceMap);
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
     * Returns one diagnostic per rule whose validator lacks its required option.
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
            $missing = $this->missing($owner, $rule, $line, $scope);

            if ($missing === null) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(
                Message::VALIDATOR_MISSING_REQUIRED_OPTION->getMessage(
                    is_int($index) ? "#{$index}" : "'{$index}'",
                    $owner->getDisplayName(),
                    $missing[0],
                    $missing[1],
                ),
            )->identifier(self::IDENTIFIER)->line($line)->build();
        }

        return $errors;
    }

    /**
     * Returns whether Yii assigns the option directly to a public instance property without running application code.
     */
    private static function isPlainPublicProperty(ClassReflection $classReflection, string $name): bool
    {
        if ($classReflection->hasNativeProperty($name) === false) {
            return false;
        }

        $property = $classReflection->getNativeProperty($name);

        return $property->isPublic()
            && $property->isStatic() === false
            && $property->isReadOnly() === false
            && $property->isHooked() === false
            && ObjectOptionAssignment::isRedeclaredByTrait($classReflection, $name) === false;
    }

    /**
     * @param DependencyTracker&Scope $scope Current analysis scope.
     *
     * @return array{string, string}|null Created class and required option the rule leaves unset, or `null`.
     */
    private function missing(ClassReflection $owner, Type $rule, int $line, Scope $scope): array|null
    {
        $validator = $this->resolver->resolve($owner, $rule, $line, $scope);

        if ($validator === null) {
            return null;
        }

        $classReflection = $validator->configuration->classReflection;

        $required = self::REQUIRED_OPTIONS[$classReflection->getName()] ?? null;

        if ($required === null || self::isPlainPublicProperty($classReflection, $required) === false) {
            return null;
        }

        // the construction key that the resolver records covers the option names of the definitions
        if (in_array($required, $this->serviceMap->getDefinitionOptionNamesById($validator->id), true)) {
            return null;
        }

        $value = null;

        foreach ($validator->configuration->options as [$name, , $optionValue]) {
            // an unknown or read-only option, a setter or an attached behavior acts before init()
            if (
                str_starts_with($name, self::EVENT_PREFIX) === false
                && self::isPlainPublicProperty($classReflection, $name) === false
            ) {
                return null;
            }

            if ($name === $required) {
                $value = $optionValue instanceof Expr ? $scope->getNativeType($optionValue) : $optionValue;
            }
        }

        if ($value === null) {
            $file = $classReflection->getFileName();

            if ($file !== null) {
                // Class signature dependencies do not include property initializer values.
                $scope->trackFileDependency($file);
            }

            $defaults = $classReflection->getNativeReflection()->getDefaultProperties();

            return array_key_exists($required, $defaults) && $defaults[$required] === null
                ? [$classReflection->getDisplayName(), $required]
                : null;
        }

        return $value->isNull()->yes()
            ? [$classReflection->getDisplayName(), $required]
            : null;
    }
}
