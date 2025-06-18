<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\property;

use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\{
    ClassReflection,
    MissingPropertyFromReflectionException,
    PropertiesClassReflectionExtension,
    PropertyReflection,
    ReflectionProvider,
};
use PHPStan\Reflection\Annotations\AnnotationsPropertiesClassReflectionExtension;
use yii\base\Component;
use yii2\extensions\phpstan\ServiceMap;

/**
 * Provides property reflection for Yii Behavior in PHPStan analysis.
 *
 * Integrates Yii Behavior with PHPStan property reflection extension, enabling detection and resolution of properties
 * provided by attached behaviors on {@see Component} subclasses during static analysis.
 *
 * This extension inspects the behaviors attached to a given class and determines if any of them provide the requested
 * property, allowing PHPStan to recognize available properties as if they were natively declared.
 *
 * Key features.
 * - Delegates to the native property if not found in behaviors.
 * - Detects properties provided by behaviors attached to {@see Component} subclasses.
 * - Ensures compatibility with PHPStan strict static analysis and autocompletion.
 * - Integrates with {@see ServiceMap} for efficient behavior lookup by class name.
 *
 * @see Component for Yii Component class.
 * @see PropertiesClassReflectionExtension for custom properties class reflection extension contract.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class BehaviorPropertiesClassReflectionExtension implements PropertiesClassReflectionExtension
{
    /**
     * Creates a new instance of the {@see BehaviorPropertiesClassReflectionExtension} class.
     *
     * @param AnnotationsPropertiesClassReflectionExtension $annotationsProperties Extension for handling
     * annotation-based properties.
     * @param ReflectionProvider $reflectionProvider Reflection provider for class and property lookups.
     * @param ServiceMap $serviceMap Service and component map for Yii Application static analysis.
     */
    public function __construct(
        private readonly AnnotationsPropertiesClassReflectionExtension $annotationsProperties,
        private readonly ReflectionProvider $reflectionProvider,
        private readonly ServiceMap $serviceMap,
    ) {}

    /**
     * Retrieves the property reflection for a given property name, including those provided by attached behaviors.
     *
     * Resolves the {@see PropertyReflection} for the specified property name on the given class, searching first among
     * properties provided by behaviors attached to the class. If the property is not found in any behavior, it
     * delegates to the native property resolution of the class. If still not found, it falls back to annotation-based
     * property resolution.
     *
     * This enables PHPStan to recognize available properties from behaviors as if they were natively declared on the
     * component class, supporting accurate static analysis and autocompletion.
     *
     * @param ClassReflection $classReflection Reflection of the class being analyzed.
     * @param string $propertyName Name of the property to resolve.
     *
     * @throws MissingPropertyFromReflectionException if the property can't be resolved from behaviors or annotations.
     *
     * @return PropertyReflection Reflection instance for the resolved property.
     */
    public function getProperty(ClassReflection $classReflection, string $propertyName): PropertyReflection
    {
        $behaviorProperty = $this->findPropertyInBehaviors($classReflection, $propertyName);

        if ($behaviorProperty !== null) {
            return $behaviorProperty;
        }

        if ($classReflection->hasNativeProperty($propertyName)) {
            return $classReflection->getNativeProperty($propertyName);
        }

        return $this->annotationsProperties->getProperty($classReflection, $propertyName);
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
     * Iterates over all behaviors attached to the given class and checks if any of them declare the requested property.
     *
     * This enables property resolution for behaviors in PHPStan static analysis, allowing detection of
     * properties that aren't natively declared on the component class but are available via attached behaviors.
     *
     * @param ClassReflection $classReflection Reflection of the class being analyzed.
     * @param string $propertyName Name of the property to resolve.
     *
     * @return PropertyReflection|null Reflection instance for the resolved property if found in a behavior; {@see null}
     * otherwise.
     */
    private function findPropertyInBehaviors(
        ClassReflection $classReflection,
        string $propertyName,
    ): PropertyReflection|null {
        $behaviors = $this->serviceMap->getBehaviorsByClassName($classReflection->getName());

        foreach ($behaviors as $behaviorClass) {
            if ($this->reflectionProvider->hasClass($behaviorClass)) {
                $behaviorReflection = $this->reflectionProvider->getClass($behaviorClass);

                if ($behaviorReflection->hasProperty($propertyName)) {
                    return $behaviorReflection->getProperty($propertyName, new OutOfClassScope());
                }
            }
        }

        return null;
    }
}
