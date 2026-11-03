<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\rules;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\{DependencyTracker, Scope};
use PHPStan\Reflection\{ClassReflection, ReflectionProvider};
use PHPStan\Rules\{IdentifierRuleError, Rule, RuleErrorBuilder};
use PHPStan\Type\{StringType, Type, TypeCombinator, TypeUtils, VerbosityLevel};
use yii\base\BaseObject;
use yii\BaseYii;
use yii2\extensions\phpstan\exception\Message;
use yii2\extensions\phpstan\ServiceMap;

use function count;
use function in_array;
use function sprintf;

/**
 * Reports {@see BaseYii::createObject()} option values that the property or setter Yii assigns them to never accepts.
 *
 * Analyses the same configurations and classes as {@see CreateObjectUnknownOptionRule}. A public instance property is
 * assigned directly and takes precedence over a setter; any other option reaches `BaseObject::__set()`, which calls a
 * public or protected `set` method. Both run without `strict_types`, so a value is reported only when PHP's weak type
 * coercion can't convert any of its possible values to the native type of the property or of the first setter
 * parameter. PHPDoc types are not consulted.
 *
 * {@see Rule} for PHPStan rule contract.
 * {@see CreateObjectConfigurationResolver} for the configurations and classes the rule analyses.
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
            $target = self::assignmentTarget($classReflection, $name);

            if ($target === null) {
                continue;
            }

            [$expectedType, $member] = $target;

            $actualType = $value instanceof Expr ? $scope->getNativeType($value) : $value;

            foreach ([...$expectedType->getReferencedClasses(), ...$actualType->getReferencedClasses()] as $className) {
                $scope->trackClassDependency($className);
            }

            if (self::isIncompatible($expectedType, $actualType) === false) {
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
     * Returns whether a native type contains the `callable` type, whose acceptance of a string or array depends on the
     * functions and methods that exist at runtime.
     */
    private static function acceptsCallable(Type $type): bool
    {
        foreach (TypeUtils::flattenTypes($type) as $member) {
            if ($member->isCallable()->yes() && $member->isObject()->yes() === false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the native type that Yii checks the option value against and the member that declares it.
     *
     * {@see BaseYii::configure()} assigns a public instance property directly, so its type applies even when a setter
     * exists. A protected, private or static property is inaccessible from there, and `BaseObject::__set()` calls the
     * `set` method instead, which must be public or protected to be callable from {@see BaseObject}. Read-only, hooked
     * and asymmetric-visibility properties, private setters, setters without parameters and setters with more than one
     * signature are left alone.
     *
     * @return array{Type, string}|null Native type and member description, or `null` when no assignment path with a
     * type check is proven.
     */
    private static function assignmentTarget(ClassReflection $classReflection, string $name): array|null
    {
        if ($name === '' || in_array($name, CreateObjectConfigurationResolver::RESERVED_OPTIONS, true)) {
            return null;
        }

        if ($classReflection->hasNativeProperty($name)) {
            $property = $classReflection->getNativeProperty($name);

            if ($property->isPublic() && $property->isStatic() === false) {
                return $property->isReadOnly()
                    || $property->isHooked()
                    || $property->isPrivateSet()
                    || $property->isProtectedSet()
                    ? null
                    : [$property->getNativeType(), 'the property'];
            }
        }

        if ($classReflection->hasNativeMethod('set' . $name) === false) {
            return null;
        }

        $setter = $classReflection->getNativeMethod('set' . $name);

        if ($setter->isPrivate()) {
            return null;
        }

        $variants = $setter->getVariants();
        $parameter = count($variants) === 1 ? $variants[0]->getParameters()[0] ?? null : null;

        return $parameter === null
            ? null
            : [
                $parameter->getNativeType(),
                sprintf('%s::%s()', $setter->getDeclaringClass()->getDisplayName(), $setter->getName()),
            ];
    }

    /**
     * Returns whether no value of the actual type converts to the expected type under weak type coercion.
     *
     * {@see Type::toCoercedArgumentType()} adds a string only for objects whose class declares `__toString()`; an
     * object of any other non-final class or interface can still be a `Stringable` subclass, and internal classes can
     * cast to string, so every object may become a string. A string or an array may name a callable that PHPStan
     * doesn't know, so a `callable` type is never claimed for them.
     */
    private static function isIncompatible(Type $expectedType, Type $actualType): bool
    {
        if (
            ($actualType->isString()->no() === false || $actualType->isArray()->no() === false)
            && self::acceptsCallable($expectedType)
        ) {
            return false;
        }

        $coercedType = $actualType->toCoercedArgumentType(false);

        if ($actualType->isObject()->no() === false) {
            $coercedType = TypeCombinator::union($coercedType, new StringType());
        }

        return $expectedType->isSuperTypeOf($coercedType)->no();
    }
}
