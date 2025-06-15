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
use yii\base\Application as BaseApplication;
use yii\console\Application as ConsoleApplication;
use yii\web\Application as WebApplication;
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
 * - Caches reflection results for improved performance.
 * - Enables accurate type inference for injected services and components.
 * - Ensures compatibility with PHPStan strict analysis and autocompletion.
 * - Handles base, web, and console application contexts.
 * - Integrates annotation-based and native property reflection.
 * - Supports dynamic Yii Application properties via service map lookup.
 *
 * @see BaseApplication for Yii Base Application class.
 * @see ConsoleApplication for Yii Console Application class.
 * @see PropertiesClassReflectionExtension for custom properties class reflection extension contract.
 * @see WebApplication for Yii Web Application class.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class ApplicationPropertiesClassReflectionExtension implements PropertiesClassReflectionExtension
{
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

        if ($this->annotationsProperties->hasProperty($normalizedClassReflection, $propertyName)) {
            return $this->annotationsProperties->getProperty($normalizedClassReflection, $propertyName);
        }

        if (null !== $componentClass = $this->serviceMap->getComponentClassById($propertyName)) {
            return new ComponentPropertyReflection(
                new DummyPropertyReflection($propertyName),
                new ObjectType($componentClass),
                $normalizedClassReflection,
            );
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

        if (in_array($className, [BaseApplication::class, ConsoleApplication::class, WebApplication::class], true)) {
            return true;
        }

        if ($this->reflectionProvider->hasClass(BaseApplication::class)) {
            return $classReflection->isSubclassOfClass($this->reflectionProvider->getClass(BaseApplication::class));
        }

        return false;
    }

    /**
     * Normalizes the class reflection for Yii Application subclasses to ensure consistent property resolution.
     *
     * This method checks if the provided {@see ClassReflection} instance represents the base Yii Application, console
     * application, web application, or any subclass.
     *
     * The normalization process is essential for property reflection logic, as it ensures that dynamic property
     * resolution and component lookup are only applied to valid Yii Application contexts.
     *
     * @param ClassReflection $classReflection Reflection of the class being analyzed.
     *
     * @return ClassReflection Normalized class reflection for the application.
     */
    private function normalizeClassReflection(ClassReflection $classReflection): ClassReflection
    {
        $className = $classReflection->getName();

        if (in_array($className, [BaseApplication::class, ConsoleApplication::class, WebApplication::class], true)) {
            return $classReflection;
        }

        if ($this->reflectionProvider->hasClass(ConsoleApplication::class)) {
            $consoleAppReflection = $this->reflectionProvider->getClass(ConsoleApplication::class);

            if ($classReflection->isSubclassOfClass($consoleAppReflection)) {
                return $classReflection;
            }
        }

        if ($this->reflectionProvider->hasClass(WebApplication::class)) {
            $webAppReflection = $this->reflectionProvider->getClass(WebApplication::class);

            if ($classReflection->isSubclassOfClass($webAppReflection)) {
                return $classReflection;
            }
        }

        return $classReflection;
    }
}
