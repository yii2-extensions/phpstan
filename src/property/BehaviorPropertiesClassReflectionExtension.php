<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\property;

use PHPStan\Analyser\{DeclarationDependencyTracker, OutOfClassScope};
use PHPStan\Reflection\{ClassReflection, PropertiesClassReflectionExtension, PropertyReflection, ReflectionProvider};
use PHPStan\ShouldNotHappenException;
use yii\base\Component;
use yii2\extensions\phpstan\{ServiceMap, ServiceMapResultCacheValueExtension};

use function sprintf;

/**
 * Resolves properties provided by behaviors attached to {@see Component} subclasses for PHPStan analysis.
 *
 * Inspects the behaviors attached to a given class via the {@see ServiceMap} and determines whether any provide the
 * requested property, allowing PHPStan to recognize behavior properties as if they were natively declared.
 *
 * {@see Component} for Yii Component class.
 * {@see PropertiesClassReflectionExtension} for custom properties class reflection extension contract.
 */
final class BehaviorPropertiesClassReflectionExtension implements PropertiesClassReflectionExtension
{
    /**
     * Creates a new instance of the {@see BehaviorPropertiesClassReflectionExtension} class.
     *
     * @param ReflectionProvider $reflectionProvider Reflection provider for class and property lookups.
     * @param ServiceMap $serviceMap Service and component map for Yii Application static analysis.
     * @param DeclarationDependencyTracker $dependencyTracker Records the configuration values each class declaration
     * depends on.
     */
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
        private readonly ServiceMap $serviceMap,
        private readonly DeclarationDependencyTracker $dependencyTracker,
    ) {}

    /**
     * Retrieves the reflection of a property provided by a behavior attached to the given class.
     *
     * PHPStan calls this method only after {@see hasProperty()} returned `true` for the same class and property name.
     *
     * @param ClassReflection $classReflection Reflection of the class being analyzed.
     * @param string $propertyName Name of the property to resolve.
     *
     * @throws ShouldNotHappenException if no attached behavior provides the property.
     *
     * @return PropertyReflection Reflection instance for the resolved property.
     */
    public function getProperty(ClassReflection $classReflection, string $propertyName): PropertyReflection
    {
        $behaviorProperty = $this->findPropertyInBehaviors($classReflection, $propertyName);

        if ($behaviorProperty === null) {
            throw new ShouldNotHappenException(
                sprintf(
                    'Property %s::$%s is not provided by any behavior attached to the class.',
                    $classReflection->getName(),
                    $propertyName,
                ),
            );
        }

        return $behaviorProperty;
    }

    /**
     * Determines whether the specified property exists on the given class, including properties provided by attached
     * behaviors.
     *
     * Checks if the class is a subclass of {@see Component} and doesn't already declare the property natively. If so,
     * inspect all behaviors attached to the class to determine if any provide the requested property.
     *
     * This enables PHPStan to recognize available properties from behaviors as if they were natively declared on the
     * component class, supporting accurate static analysis and autocompletion.
     *
     * @param ClassReflection $classReflection Reflection of the class being analyzed.
     * @param string $propertyName Name of the property to resolve.
     *
     * @return bool `true` if the property exists on the class via an attached behavior; `false` otherwise.
     */
    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        if ($classReflection->isSubclassOfClass($this->reflectionProvider->getClass(Component::class)) === false) {
            return false;
        }

        if ($classReflection->hasNativeProperty($propertyName)) {
            return false;
        }

        return $this->findPropertyInBehaviors($classReflection, $propertyName) !== null;
    }

    /**
     * Searches for a property provided by behaviors attached to the specified class.
     *
     * Iterates over all behaviors attached to the given class and returns the first instance property with the
     * requested name; static behavior properties are skipped, because Yii reads a behavior property as
     * `$behavior->$name`.
     *
     * This enables property resolution for behaviors in PHPStan static analysis, allowing detection of
     * properties that aren't natively declared on the component class but are available via attached behaviors.
     *
     * @param ClassReflection $classReflection Reflection of the class being analyzed.
     * @param string $propertyName Name of the property to resolve.
     *
     * @return PropertyReflection|null Reflection instance for the resolved property if found in a behavior; `null`
     * otherwise.
     */
    private function findPropertyInBehaviors(
        ClassReflection $classReflection,
        string $propertyName,
    ): PropertyReflection|null {
        $this->dependencyTracker->trackValueDependency(
            $classReflection,
            ServiceMapResultCacheValueExtension::class,
            ServiceMapResultCacheValueExtension::behaviorsKey($classReflection->getName()),
        );

        $behaviors = $this->serviceMap->getBehaviorsByClassName($classReflection->getName());

        foreach ($behaviors as $behaviorClass) {
            $this->dependencyTracker->trackClassDependency($classReflection, $behaviorClass);

            if ($this->reflectionProvider->hasClass($behaviorClass)) {
                $behaviorReflection = $this->reflectionProvider->getClass($behaviorClass);

                if ($behaviorReflection->hasInstanceProperty($propertyName)) {
                    return $behaviorReflection->getInstanceProperty($propertyName, new OutOfClassScope());
                }
            }
        }

        return null;
    }
}
