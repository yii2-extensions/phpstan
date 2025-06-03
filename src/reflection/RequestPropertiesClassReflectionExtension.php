<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\reflection;

use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\{
    ClassReflection,
    MissingPropertyFromReflectionException,
    PropertiesClassReflectionExtension,
    PropertyReflection,
    ReflectionProvider,
};

/**
 * Provides property reflection for Yii console request classes in PHPStan analysis.
 *
 * Integrates Yii's web request property reflection into the console request context, enabling accurate type inference
 * and autocompletion for properties that are available on both web and console request classes.
 *
 * This extension allows PHPStan to recognize and reflect properties on the Yii console request instance that are
 * originally defined on the web request class, even if they aren't declared as native properties on the console
 * request.
 *
 * The implementation delegates property lookups to the web request class reflection, ensuring that all valid
 * properties, whether declared natively or inherited—are available for static analysis and IDE support.
 *
 * Key features.
 * - Delegates property reflection to the web request class.
 * - Enables accurate type inference for shared request properties.
 * - Ensures compatibility with PHPStan's strict analysis and autocompletion.
 * - Handles both console and web request contexts.
 * - Supports dynamic property resolution for console requests.
 *
 * @see PropertiesClassReflectionExtension for custom properties class reflection extension contract.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class RequestPropertiesClassReflectionExtension implements PropertiesClassReflectionExtension
{
    /**
     * Creates a new instance of the {@see RequestPropertiesClassReflectionExtension} class.
     *
     * @param ReflectionProvider $reflectionProvider Reflection provider for class and property lookups.
     */
    public function __construct(private readonly ReflectionProvider $reflectionProvider) {}

    /**
     * Retrieves the property reflection for a given property on the Yii console request class by delegating to the web
     * request class.
     *
     * Resolves the property reflection for the specified property name by looking it up on the web request class.
     *
     * This ensures that properties available on the web request are also accessible for static analysis and IDE support
     * on the console request.
     *
     * @param ClassReflection $classReflection Class reflection instance for the Yii console request.
     * @param string $propertyName Name of the property to retrieve.
     *
     * @throws MissingPropertyFromReflectionException if the property doesn't exist on the web request class.
     *
     * @return PropertyReflection Property reflection instance for the specified property.
     */
    public function getProperty(ClassReflection $classReflection, string $propertyName): PropertyReflection
    {
        return $this->reflectionProvider
            ->getClass(\yii\web\Request::class)
            ->getProperty($propertyName, new OutOfClassScope());
    }

    /**
     * Determines whether the specified property exists on the Yii console request class by delegating to the web
     * request class.
     *
     * Checks for the existence of a property on the console request instance by verifying if the corresponding property
     * exists on the web request class.
     *
     * This enables static analysis tools and IDEs to provide accurate autocompletion and type inference for shared
     * request properties.
     *
     * @param ClassReflection $classReflection Class reflection instance for the Yii console request.
     * @param string $propertyName Name of the property to check for existence.
     *
     * @return bool `true` if the property exists on the web request class; `false` otherwise.
     */
    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        if ($classReflection->getName() !== \yii\console\Request::class) {
            return false;
        }

        return $this->reflectionProvider->getClass(\yii\web\Request::class)->hasProperty($propertyName);
    }
}
