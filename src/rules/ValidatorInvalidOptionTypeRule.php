<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\rules;

use Closure;
use PhpParser\Node;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\{DependencyTracker, Scope};
use PHPStan\Reflection\{ClassReflection, ReflectionProvider};
use PHPStan\Rules\{IdentifierRuleError, Rule, RuleErrorBuilder};
use PHPStan\Type\{ObjectType, Type, VerbosityLevel};
use yii2\extensions\phpstan\exception\Message;
use yii2\extensions\phpstan\ServiceMap;

use function in_array;
use function is_int;

/**
 * Reports option values written in model validation rules that the property or setter of the validator Yii creates
 * never accepts.
 *
 * A value is reported only when PHP's weak type coercion can't convert its native type to the native type of the public
 * property or of the first setter parameter; PHPDoc types are not consulted. Only options that the rule writes and Yii
 * assigns unchanged are reported: alias defaults, `attributes` and the `method` of a closure rule, which
 * `Validator::createValidator()` replaces with the closure, are not.
 *
 * {@see ValidatorConfigurationResolver} for the dispatch, including the declared alias table assumption.
 * {@see CreateObjectConfigurationResolver::rejectedOptions()} for the checks.
 *
 * @implements Rule<Return_>
 */
final readonly class ValidatorInvalidOptionTypeRule implements Rule
{
    /**
     * Error identifier reported by this rule.
     */
    private const string IDENTIFIER = 'yii2.validator.invalidOptionType';
    /**
     * Option to which `Validator::createValidator()` assigns the closure of a closure rule.
     */
    private const string INLINE_METHOD_OPTION = 'method';
    /**
     * Options whose written value `Validator::createValidator()` always replaces.
     */
    private const array REPLACED_OPTIONS = ['attributes'];

    /**
     * Resolves the validator class and configuration Yii constructs.
     */
    private ValidatorConfigurationResolver $resolver;

    /**
     * Initializes static class reflection and the analysis service map.
     */
    public function __construct(ReflectionProvider $reflectionProvider, ServiceMap $serviceMap)
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
     * Returns one diagnostic per written option whose value the created validator never accepts.
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
            $rejected = $this->rejected($owner, $rule, $line, $scope);

            foreach ($rejected as [$class, $name, $actualType, $expectedType, $member]) {
                $errors[] = RuleErrorBuilder::message(
                    Message::VALIDATOR_INVALID_OPTION_TYPE->getMessage(
                        is_int($index) ? "#{$index}" : "'{$index}'",
                        $owner->getDisplayName(),
                        $class,
                        $name,
                        $actualType->describe(VerbosityLevel::precise()),
                        $member,
                        $expectedType->describe(VerbosityLevel::typeOnly()),
                    ),
                )->identifier(self::IDENTIFIER)->line($line)->build();
            }
        }

        return $errors;
    }

    /**
     * @param DependencyTracker&Scope $scope Current analysis scope.
     *
     * @return list<array{string, string, Type, Type, string}> Created class, option name, value type, expected native
     * type and member description of each rejected option written in the rule.
     */
    private function rejected(ClassReflection $owner, Type $rule, int $line, Scope $scope): array
    {
        $validator = $this->resolver->resolve($owner, $rule, $line, $scope);

        if ($validator === null) {
            return [];
        }

        $configuration = $validator->configuration;

        $replaced = self::REPLACED_OPTIONS;

        $type = ModelRulesReader::ruleValues($rule)[1] ?? null;

        if ($type !== null && (new ObjectType(Closure::class))->isSuperTypeOf($type)->yes()) {
            $replaced[] = self::INLINE_METHOD_OPTION;
        }

        $rejected = [];

        foreach (
            CreateObjectConfigurationResolver::rejectedOptions($configuration, $scope) as [
                $name,
                ,
                $actualType,
                $expectedType,
                $member,
            ]
        ) {
            if (in_array($name, $validator->writtenOptions, true) && in_array($name, $replaced, true) === false) {
                $rejected[] = [
                    $configuration->classReflection->getDisplayName(),
                    $name,
                    $actualType,
                    $expectedType,
                    $member,
                ];
            }
        }

        return $rejected;
    }
}
