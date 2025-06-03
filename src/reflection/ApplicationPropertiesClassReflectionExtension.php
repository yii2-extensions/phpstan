<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\reflection;

use PHPStan\Reflection\{
    ClassReflection,
    MissingPropertyFromReflectionException,
    PropertiesClassReflectionExtension,
    PropertyReflection,
    ReflectionProvider,
};
use PHPStan\Reflection\Annotations\AnnotationsPropertiesClassReflectionExtension;
use PHPStan\Reflection\Dummy\DummyPropertyReflection;
use PHPStan\Type\ObjectType;
use yii2\extensions\phpstan\ServiceMap;

/**
 * Provides property reflection for Yii application components in PHPStan analysis.
 *
 * Integrates Yii's dependency injection and service map with PHPStan's property reflection system, enabling accurate
 * type inference and autocompletion for dynamic application components and services.
 *
 * This extension allows PHPStan to recognize properties on the Yii application instance that are defined via
 * configuration, dependency injection, or service mapping, even if they aren't declared as native properties.
 *
 * The implementation delegates to annotation-based property extensions, native property reflection, and a custom
 * service map for component resolution.
 *
 * This ensures that all valid application properties whether declared, annotated, or registered as components are
 * available for static analysis and IDE support.
 *
 * Key features.
 * - Enables accurate type inference for injected services and components.
 * - Ensures compatibility with PHPStan's strict analysis and autocompletion.
 * - Handles both base and web application contexts.
 * - Integrates annotation-based and native property reflection.
 * - Supports dynamic Yii application properties via service map lookup.
 *
 * @see AnnotationsPropertiesClassReflectionExtension for annotation support.
 * @see PropertiesClassReflectionExtension for custom properties class reflection extension contract.
 * @see ServiceMap for component class resolution.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class ApplicationPropertiesClassReflectionExtension implements PropertiesClassReflectionExtension
{
    /**
     * Creates a new instance of the {@see ApplicationPropertiesClassReflectionExtension) class.
     *
     * @param AnnotationsPropertiesClassReflectionExtension $annotationsProperties Extension for handling
     * annotation-based properties.
     * @param ReflectionProvider $reflectionProvider Reflection provider for class and property lookups.
     * @param ServiceMap $serviceMap Service map for resolving component classes by ID.
     */
    public function __construct(
        private readonly AnnotationsPropertiesClassReflectionExtension $annotationsProperties,
        private readonly ReflectionProvider $reflectionProvider,
        private readonly ServiceMap $serviceMap,
    ) {}

    /**
     * Retrieves the property reflection for a given property on the Yii application class or its components.
     *
     * Resolves the property reflection for the specified property name by checking for dynamic components, native
     * properties, and annotation-based properties on the Yii application instance.
     *
     * This method ensures that properties defined via configuration, dependency injection, or service mapping are
     * accessible for static analysis and IDE support.
     *
     * The method normalizes the context to the web application class for consistent property resolution.
     *
     * If the property corresponds to a registered component, it returns a {@see ComponentPropertyReflection} with the
     * appropriate type.
     *
     * Otherwise, it checks for native or annotation-based properties and returns the corresponding reflection.
     *
     * @param ClassReflection $classReflection Class reflection instance for the Yii application.
     * @param string $propertyName Name of the property to retrieve.
     *
     * @throws MissingPropertyFromReflectionException if the property doesn't exist or can't be resolved.
     * @return PropertyReflection Property reflection instance for the specified property.
     */
    public function getProperty(ClassReflection $classReflection, string $propertyName): PropertyReflection
    {
        if ($classReflection->getName() !== \yii\web\Application::class) {
            $classReflection = $this->reflectionProvider->getClass(\yii\web\Application::class);
        }

        if (null !== $componentClass = $this->serviceMap->getComponentClassById($propertyName)) {
            return new ComponentPropertyReflection(
                new DummyPropertyReflection($propertyName),
                new ObjectType($componentClass),
            );
        }

        if ($classReflection->hasNativeProperty($propertyName)) {
            return $classReflection->getNativeProperty($propertyName);
        }

        return $this->annotationsProperties->getProperty($classReflection, $propertyName);
    }

    /**
     * Determines whether the specified property exists on the Yii application class or its components.
     *
     * Checks for the existence of a property on the Yii application instance by considering native properties,
     * annotation-based properties, and dynamic components registered via the service map.
     *
     * This method ensures compatibility with both base and web application contexts, enabling accurate property
     * reflection for static analysis and IDE autocompletion.
     *
     * The method first verifies that the provided class is either the base application or a subclass.
     *
     * For subclasses, it normalizes the context to the web application class to ensure consistent property resolution.
     *
     * It then checks for the property as a native property, an annotation-based property, or a registered component.
     *
     * @param ClassReflection $classReflection Class reflection instance for the Yii application.
     * @param string $propertyName Name of the property to check for existence.
     *
     * @return bool `true` if the property exists as a native, annotated, or component property; `false` otherwise.
     */
    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        $reflectionProviderBaseApplication = $this->reflectionProvider->getClass(\yii\base\Application::class);

        if (
            $classReflection->getName() !== \yii\base\Application::class &&
            $classReflection->isSubclassOfClass($reflectionProviderBaseApplication) === false
        ) {
            return false;
        }

        if ($classReflection->getName() !== \yii\web\Application::class) {
            $classReflection = $this->reflectionProvider->getClass(\yii\web\Application::class);
        }

        return $classReflection->hasNativeProperty($propertyName)
            || $this->annotationsProperties->hasProperty($classReflection, $propertyName)
            || $this->serviceMap->getComponentClassById($propertyName);
    }
}
