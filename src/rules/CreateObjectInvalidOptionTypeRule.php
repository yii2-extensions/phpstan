<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\rules;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\{DependencyTracker, Scope};
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\{IdentifierRuleError, Rule, RuleErrorBuilder};
use PHPStan\Type\{NullType, ObjectType, ObjectWithoutClassType, StringType, Type, TypeCombinator, VerbosityLevel};
use yii\BaseYii;
use yii\di\Instance;
use yii2\extensions\phpstan\exception\Message;
use yii2\extensions\phpstan\ServiceMap;

use function in_array;

/**
 * Reports {@see BaseYii::createObject()} option values that the property or setter Yii assigns them to never accepts.
 *
 * A value is reported only when PHP's weak type coercion can't convert it to the native type of the public property or
 * of the first setter parameter. PHPDoc types are not consulted. A value that may be an {@see Instance} reference is
 * also checked as the object or `null` that the container may resolve it to before the assignment.
 *
 * {@see Rule} for PHPStan rule contract.
 * {@see CreateObjectConfigurationResolver} for the configurations and classes the rule analyses.
 * {@see ObjectOptionAssignment} for the assignment semantics.
 *
 * @implements Rule<StaticCall>
 */
final class CreateObjectInvalidOptionTypeRule implements Rule
{
    /**
     * Error identifier reported by this rule.
     */
    private const string IDENTIFIER = 'yii2.createObject.invalidOptionType';
    /**
     * Resolves the configurations whose assignment path is certain.
     */
    private readonly CreateObjectConfigurationResolver $configurationResolver;

    /**
     * Initializes the rule with class reflection and the analysis configuration.
     *
     * @param ReflectionProvider $reflectionProvider Provides class reflection for the created class.
     * @param ServiceMap $serviceMap Service and component map for Yii Application static analysis.
     */
    public function __construct(ReflectionProvider $reflectionProvider, ServiceMap $serviceMap)
    {
        $this->configurationResolver = new CreateObjectConfigurationResolver($reflectionProvider, $serviceMap);
    }

    public function getNodeType(): string
    {
        return StaticCall::class;
    }

    /**
     * Returns one error per distinct option whose value the created class never accepts.
     *
     * @param StaticCall $node Static call to inspect.
     * @param DependencyTracker&Scope $scope Current PHPStan analysis scope.
     *
     * @return list<IdentifierRuleError> Errors reported at the first key of each option, or at the call line when the
     * configuration doesn't locate the key.
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $configuration = $this->configurationResolver->resolve($node, $scope);

        if ($configuration === null) {
            return [];
        }

        $classReflection = $configuration->classReflection;
        $errors = [];

        foreach ($configuration->options as [$name, $line, $value]) {
            if (in_array($name, CreateObjectConfigurationResolver::RESERVED_OPTIONS, true)) {
                continue;
            }

            $actualType = $value instanceof Expr ? $scope->getNativeType($value) : $value;

            $target = ObjectOptionAssignment::rejectingTarget($classReflection, $name, $actualType, $scope);

            if ($target === null) {
                continue;
            }

            [$expectedType, $member] = $target;

            if (self::mayResolveToAccepted($expectedType, $actualType)) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(
                Message::CREATE_OBJECT_INVALID_OPTION_TYPE->getMessage(
                    $configuration->calledClass,
                    $classReflection->getDisplayName(),
                    $name,
                    $actualType->describe(VerbosityLevel::precise()),
                    $member,
                    $expectedType->describe(VerbosityLevel::typeOnly()),
                ),
            )
                ->identifier(self::IDENTIFIER)
                ->line($line)
                ->build();
        }

        return $errors;
    }

    /**
     * Returns whether the value may be an {@see Instance} reference that the container resolves, before the assignment,
     * to an object or `null` ({@see Instance::get()}) that the expected type may accept.
     *
     * `Container::build()` resolves references among the options of the configuration, and nested in a constructor
     * argument only when the container resolves arrays, a runtime setting. Any object may also be a `Stringable`.
     */
    private static function mayResolveToAccepted(Type $expectedType, Type $actualType): bool
    {
        if ((new ObjectType(Instance::class))->isSuperTypeOf($actualType)->no()) {
            return false;
        }

        $resolvedType = TypeCombinator::union(new ObjectWithoutClassType(), new StringType(), new NullType());

        return $expectedType->isSuperTypeOf($resolvedType)->no() === false;
    }
}
