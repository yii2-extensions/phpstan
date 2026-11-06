<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\rules;

use PHPStan\Analyser\{DependencyTracker, Scope};
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\{StringType, Type, TypeCombinator, TypeUtils};
use yii\base\{BaseObject, Component};
use yii\BaseYii;
use yii2\extensions\phpstan\{ServiceMap, ServiceMapResultCacheValueExtension};

use function count;
use function in_array;
use function sprintf;
use function str_starts_with;

/**
 * Decides how Yii assigns configuration options to a {@see BaseObject} or {@see Component} subclass through
 * {@see BaseYii::configure()} and `__set()`, independently of how the object is constructed.
 *
 * Callers pass only the options that Yii assigns, in assignment order: a construction API that removes keys before the
 * assignment, such as the `class` and `__class` keys that {@see BaseYii::createObject()} consumes from its `$type`
 * configuration, removes them first, and keeps every key of a configuration that Yii assigns as is.
 */
final class ObjectOptionAssignment
{
    /**
     * Option name prefix with which `Component::__set()` attaches a behavior.
     */
    private const string BEHAVIOR_PREFIX = 'as ';
    /**
     * Option name prefix with which `Component::__set()` attaches an event handler.
     */
    private const string EVENT_PREFIX = 'on ';

    /**
     * Returns whether the class is instantiable and inherits the constructor of the contract and the `__set()` of
     * {@see BaseObject} or {@see Component}, so that its configuration is assigned by {@see BaseYii::configure()}.
     *
     * @param CreateObjectConstructionContract|null $contract Construction contract, `null` for
     * {@see CreateObjectConstructionContract::baseObject()}.
     */
    public static function isConfigurable(
        ClassReflection $classReflection,
        CreateObjectConstructionContract|null $contract = null,
    ): bool {
        $contract ??= CreateObjectConstructionContract::baseObject();

        return $classReflection->isClass()
            && $classReflection->isAbstract() === false
            && $contract->isInheritedConstructor($classReflection)
            && self::isDeclaredBy($classReflection, '__set', [BaseObject::class, Component::class]);
    }

    /**
     * Returns the native type that rejects every value of the actual type and the member that declares it, or `null`
     * when Yii assigns the option to no member with a type check or the member may accept a value of that type.
     *
     * Records the declaration dependency of every class named by both types.
     *
     * @param string $name Option name, compared as Yii does.
     * @param Type $actualType Native type of the value Yii assigns.
     * @param DependencyTracker&Scope $scope Current PHPStan analysis scope.
     *
     * @return array{Type, string}|null Native type and member description.
     */
    public static function rejectingTarget(
        ClassReflection $classReflection,
        string $name,
        Type $actualType,
        Scope $scope,
    ): array|null {
        $target = self::assignmentTarget($classReflection, $name);

        if ($target === null) {
            return null;
        }

        [$expectedType] = $target;

        foreach ([...$expectedType->getReferencedClasses(), ...$actualType->getReferencedClasses()] as $className) {
            $scope->trackClassDependency($className);
        }

        return self::isIncompatible($expectedType, $actualType) ? $target : null;
    }

    /**
     * Returns the options that the class can't assign, in configuration order.
     *
     * For a {@see Component} subclass, `on ...` and `as ...` options are never returned, and other options only when no
     * behavior can receive them: `behaviors()` and `ensureBehaviors()` inherited from {@see Component}, no `as ...`
     * option, no entry of the analysis `behaviors` map for any ancestor, and no earlier option that runs a setter, a
     * set hook or an overridden `on()`.
     *
     * @param list<array{string, int, mixed}> $options Name, reported line and value of each option Yii assigns.
     * @param ServiceMap $serviceMap Service and component map for Yii Application static analysis.
     * @param DependencyTracker&Scope $scope Current PHPStan analysis scope.
     *
     * @return list<array{string, int}> Name and reported line of each unknown option.
     */
    public static function unknownOptions(
        ClassReflection $classReflection,
        array $options,
        ServiceMap $serviceMap,
        Scope $scope,
    ): array {
        $isComponent = self::isDeclaredBy($classReflection, '__set', [Component::class]);
        $withoutBehaviors = $isComponent === false
            || self::isWithoutBehaviors($classReflection, $options, $serviceMap, $scope);

        $codeRan = false;
        $unknown = [];

        foreach ($options as [$name, $line]) {
            $isUnknown = $isComponent
                ? $withoutBehaviors && $codeRan === false && self::isUnknownComponentOption($classReflection, $name)
                : self::isUnknownOption($classReflection, $name);

            $codeRan = $codeRan || ($isComponent && self::mayRunCode($classReflection, $name));

            if ($isUnknown) {
                $unknown[] = [$name, $line];
            }
        }

        return $unknown;
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
     * exists. A protected, private or static property is inaccessible from there, and `__set()` calls the `set` method
     * instead, which must be public or protected to be callable from {@see BaseObject} or {@see Component}. Read-only,
     * hooked and asymmetric-visibility properties, properties that a trait redeclares differently, private setters,
     * setters without parameters and setters with more than one signature are left alone.
     *
     * @return array{Type, string}|null Native type and member description, or `null` when no assignment path with a
     * type check is proven.
     */
    private static function assignmentTarget(ClassReflection $classReflection, string $name): array|null
    {
        if ($name === '') {
            return null;
        }

        if ($classReflection->hasNativeProperty($name)) {
            if (self::isRedeclaredByTrait($classReflection, $name)) {
                return null;
            }

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
     * Returns whether the native method of the class, possibly inherited, is declared by one of the given classes.
     *
     * @param list<string> $declaringClasses Fully qualified names of the accepted declaring classes.
     */
    private static function isDeclaredBy(
        ClassReflection $classReflection,
        string $methodName,
        array $declaringClasses,
    ): bool {
        return $classReflection->hasNativeMethod($methodName)
            && in_array(
                $classReflection->getNativeMethod($methodName)->getDeclaringClass()->getName(),
                $declaringClasses,
                true,
            );
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

    /**
     * Returns whether a trait of the class or of a parent class up to the declaring class of the native property, or a
     * trait of those traits, declares the property with other visibility, hook, `static` or `readonly` modifiers than
     * the declaration PHPStan reports, as a PHP 8.5 trait that redeclares an inherited property may do.
     *
     * The declaring class shadows the traits of its ancestors except for their hooks, which a redeclaration without
     * hooks inherits, so only the hooks of those traits are compared.
     */
    private static function isRedeclaredByTrait(ClassReflection $classReflection, string $name): bool
    {
        $property = $classReflection->getNativeProperty($name);
        $declaringClass = $property->getDeclaringClass()->getName();
        $shadowed = false;

        for ($class = $classReflection; $class !== null; $class = $class->getParentClass()) {
            // a trait also declares the properties of the traits it uses
            foreach ($class->getTraits() as $trait) {
                if ($trait->hasNativeProperty($name) === false) {
                    continue;
                }

                $declaration = $trait->getNativeProperty($name);

                if (
                    $declaration->isHooked() !== $property->isHooked()
                    || $shadowed === false && (
                        $declaration->isPublic() !== $property->isPublic()
                        || $declaration->isPrivate() !== $property->isPrivate()
                        || $declaration->isStatic() !== $property->isStatic()
                        || $declaration->isReadOnly() !== $property->isReadOnly()
                        || $declaration->isPrivateSet() !== $property->isPrivateSet()
                        || $declaration->isProtectedSet() !== $property->isProtectedSet()
                    )
                ) {
                    return true;
                }
            }

            $shadowed = $shadowed || $class->getName() === $declaringClass;
        }

        return false;
    }

    /**
     * Returns whether {@see isUnknownOption()} holds for a name that isn't an `on ...` event, compared case-sensitively
     * as Yii does.
     */
    private static function isUnknownComponentOption(ClassReflection $classReflection, string $name): bool
    {
        return str_starts_with($name, self::EVENT_PREFIX) === false && self::isUnknownOption($classReflection, $name);
    }

    /**
     * Returns whether assigning the option from outside the class reaches `BaseObject::__set()` and throws
     * `yii\base\UnknownPropertyException`.
     *
     * A public property is assigned directly. A protected or private property is inaccessible from
     * {@see BaseYii::configure()}, so `__set()` handles it like an absent one. `__set()` calls any `set` method,
     * whatever its visibility, and rejects a name with only a `get` method as read-only; both are left alone, as are
     * empty names, static, read-only and hooked properties, and properties that a trait redeclares differently.
     */
    private static function isUnknownOption(ClassReflection $classReflection, string $name): bool
    {
        if ($name === '') {
            return false;
        }

        if ($classReflection->hasNativeProperty($name)) {
            if (self::isRedeclaredByTrait($classReflection, $name)) {
                return false;
            }

            $property = $classReflection->getNativeProperty($name);

            if ($property->isPublic() || $property->isStatic() || $property->isReadOnly() || $property->isHooked()) {
                return false;
            }
        }

        return $classReflection->hasNativeMethod('set' . $name) === false
            && $classReflection->hasNativeMethod('get' . $name) === false;
    }

    /**
     * Returns whether no behavior is attached when Yii configures the {@see Component} subclass: `behaviors()` and
     * `ensureBehaviors()` inherited from {@see Component}, no `as ...` option and no map entry for any ancestor.
     *
     * Records the `behaviors` value dependency of every ancestor before deciding.
     *
     * @param list<array{string, int, mixed}> $options Name, reported line and value of each option Yii assigns.
     * @param DependencyTracker&Scope $scope Current PHPStan analysis scope.
     */
    private static function isWithoutBehaviors(
        ClassReflection $classReflection,
        array $options,
        ServiceMap $serviceMap,
        Scope $scope,
    ): bool {
        if (
            self::isDeclaredBy($classReflection, 'behaviors', [Component::class]) === false
            || self::isDeclaredBy($classReflection, 'ensureBehaviors', [Component::class]) === false
        ) {
            return false;
        }

        foreach ($options as [$name]) {
            if (str_starts_with($name, self::BEHAVIOR_PREFIX)) {
                return false;
            }
        }

        $withoutBehaviors = true;

        foreach ($classReflection->getAncestors() as $ancestor) {
            $scope->trackValueDependency(
                ServiceMapResultCacheValueExtension::class,
                ServiceMapResultCacheValueExtension::behaviorsKey($ancestor->getName()),
            );

            if ($serviceMap->getBehaviorsByClassName($ancestor->getName()) !== []) {
                $withoutBehaviors = false;
            }
        }

        return $withoutBehaviors;
    }

    /**
     * Returns whether assigning the option may run application code that attaches a behavior for a later option: a set
     * hook, a `set` method of any visibility, an `on ...` event with an overridden `on()`, or a property that a trait
     * redeclares differently, which may add a set hook.
     */
    private static function mayRunCode(ClassReflection $classReflection, string $name): bool
    {
        if ($classReflection->hasNativeProperty($name)) {
            if (self::isRedeclaredByTrait($classReflection, $name)) {
                return true;
            }

            $property = $classReflection->getNativeProperty($name);

            if ($property->isPublic() && $property->isStatic() === false) {
                return $property->isHooked();
            }
        }

        if ($classReflection->hasNativeMethod('set' . $name)) {
            return true;
        }

        return str_starts_with($name, self::EVENT_PREFIX)
            && self::isDeclaredBy($classReflection, 'on', [Component::class]) === false;
    }
}
