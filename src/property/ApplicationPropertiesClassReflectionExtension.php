<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\property;

use PHPStan\Reflection\{
    ClassReflection,
    MissingPropertyFromReflectionException,
    PropertiesClassReflectionExtension,
    PropertyReflection,
    ReflectionProvider,
};
use PHPStan\Reflection\Annotations\AnnotationsPropertiesClassReflectionExtension;
use PHPStan\Reflection\Dummy\DummyPropertyReflection;
use PHPStan\Type\{MixedType, ObjectType};
use yii2\extensions\phpstan\reflection\ComponentPropertyReflection;
use yii2\extensions\phpstan\ServiceMap;

use function in_array;

/**
 * Provides property reflection for a Yii Application component in PHPStan analysis.
 *
 * Integrates Yii DI container service resolution and service map with PHPStan property reflection system, enabling
 * accurate type inference and autocompletion for dynamic application components and services.
 *
 * This extension allows PHPStan to recognize properties on the Yii Application instance that are defined via
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
 * - Ensures compatibility with PHPStan strict analysis and autocompletion.
 * - Handles base, web, and console application contexts.
 * - Integrates annotation-based and native property reflection.
 * - Supports dynamic Yii Application properties via service map lookup.
 *
 * @see \yii\base\Application::class for Yii Base Application class.
 * @see \yii\console\Application::class for Yii Console Application class.
 * @see \yii\web\Application::class for Yii Web Application class.
 * @see PropertiesClassReflectionExtension for custom properties class reflection extension contract.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class ApplicationPropertiesClassReflectionExtension implements PropertiesClassReflectionExtension
{
    /**
     * List of supported Yii Application classes for property reflection.
     *
     * This array contains the fully qualified class names of the Yii Application base, console, and web application
     * classes that this extension supports for dynamic property resolution.
     *
     * It ensures that the extension only applies to valid Yii Application contexts, enabling accurate property
     * reflection and IDE autocompletion.
     *
     * @var array<int, class-string|string>
     * @phpstan-var array<int, class-string|string>
     */
    private const SUPPORTED_APPLICATION_CLASSES = [
        \yii\base\Application::class,
        \yii\console\Application::class,
        \yii\web\Application::class,
    ];

    /**
     * Creates a new instance of the {@see ApplicationPropertiesClassReflectionExtension} class.
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
     * Retrieves the property reflection for a given property on the Yii Application class or its components.
     *
     * Resolves the property reflection for the specified property name by checking for dynamic components, native
     * properties, and annotation-based properties on the Yii Application instance.
     *
     * This method ensures that properties defined via configuration, dependency injection, or service mapping are
     * accessible for static analysis and IDE support.
     *
     * @param ClassReflection $classReflection Reflection of the class being analyzed.
     * @param string $propertyName Name of the property to resolve.
     *
     * @throws MissingPropertyFromReflectionException if the property doesn't exist or can't be resolved.
     *
     * @return PropertyReflection Property reflection instance for the specified property.
     */
    public function getProperty(ClassReflection $classReflection, string $propertyName): PropertyReflection
    {
        $normalizedClassReflection = $this->normalizeClassReflection($classReflection);

        if ($normalizedClassReflection->hasNativeProperty($propertyName)) {
            return $normalizedClassReflection->getNativeProperty($propertyName);
        }

        if (null !== $componentClass = $this->serviceMap->getComponentClassById($propertyName)) {
            return new ComponentPropertyReflection(
                new DummyPropertyReflection($propertyName),
                new ObjectType($componentClass),
                $normalizedClassReflection,
            );
        }

        if ($this->annotationsProperties->hasProperty($normalizedClassReflection, $propertyName)) {
            return $this->annotationsProperties->getProperty($normalizedClassReflection, $propertyName);
        }

        return new ComponentPropertyReflection(
            new DummyPropertyReflection($propertyName),
            new MixedType(),
            $normalizedClassReflection,
        );
    }

    /**
     * Determines whether the specified property exists on the Yii Application class or its components.
     *
     * Checks for the existence of a property on the Yii Application instance by considering native properties,
     * annotation-based properties, and dynamic components registered via the service map.
     *
     * This method ensures compatibility with base, web, and console application contexts, enabling accurate property
     * reflection for static analysis and IDE autocompletion.
     *
     * @param ClassReflection $classReflection Reflection of the class being analyzed.
     * @param string $propertyName Name of the property to resolve.
     *
     * @return bool `true` if the property exists as a native, annotated, or component property; `false` otherwise.
     */
    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        if ($this->isApplicationClass($classReflection) === false) {
            return false;
        }

        $normalizedClassReflection = $this->normalizeClassReflection($classReflection);

        return $normalizedClassReflection->hasNativeProperty($propertyName)
            || $this->annotationsProperties->hasProperty($normalizedClassReflection, $propertyName)
            || $this->serviceMap->getComponentClassById($propertyName) !== null;
    }

    /**
     * Determines if the provided class reflection corresponds to a Yii Application class or its subclass.
     *
     * This method checks whether the given {@see ClassReflection} instance represents the base Yii Application, the
     * console or web application, or any subclass.
     *
     * This check is essential for restricting property reflection logic to valid Yii Application contexts, ensuring
     * that dynamic property resolution is only applied where appropriate.
     *
     * @param ClassReflection $classReflection Reflection of the class being analyzed.
     *
     * @return bool `true` if the class is a Yii Application or subclass; `false` otherwise.
     */
    private function isApplicationClass(ClassReflection $classReflection): bool
    {
        $className = $classReflection->getName();

        if (in_array($className, self::SUPPORTED_APPLICATION_CLASSES, true)) {
            return true;
        }

        if ($this->reflectionProvider->hasClass(\yii\base\Application::class)) {
            return $classReflection->isSubclassOfClass(
                $this->reflectionProvider->getClass(\yii\base\Application::class),
            );
        }

        return false;
    }

    /**
     * Normalizes the class reflection for Yii Application subclasses to ensure consistent property resolution.
     *
     * This method uses the configured application type from the service map to provide a consistent application
     * class reflection, resolving the union type issue in PHPStan analysis.
     *
     * The normalization process ensures that dynamic property resolution and component lookup use the explicitly
     * configured application type rather than attempting to infer it from context.
     *
     * @param ClassReflection $classReflection Reflection of the class being analyzed.
     *
     * @return ClassReflection Normalized class reflection for the configured application type.
     */
    private function normalizeClassReflection(ClassReflection $classReflection): ClassReflection
    {
        $configuredApplicationType = $this->serviceMap->getApplicationType();

        if ($this->reflectionProvider->hasClass($configuredApplicationType)) {
            return $this->reflectionProvider->getClass($configuredApplicationType);
        }

        return $classReflection;
    }
}
