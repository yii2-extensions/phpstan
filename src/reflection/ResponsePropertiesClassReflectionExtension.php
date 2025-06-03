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
 * Provides property reflection for Yii console response classes in PHPStan analysis.
 *
 * Integrates Yii's web response properties into the console response context, enabling accurate type inference and
 * autocompletion for properties that are available on both web and console response classes.
 *
 * This extension allows PHPStan to recognize and reflect properties on the Yii console response instance that are
 * originally defined on the web response class, even if they aren't declared as native properties on the console
 * response.
 *
 * The implementation delegates property lookups to the web response class reflection, ensuring that all valid
 * properties, whether declared natively or inherited, are available for static analysis and IDE support.
 *
 * Key features.
 * - Delegates property reflection to the web response class.
 * - Enables accurate type inference for shared response properties.
 * - Ensures compatibility with PHPStan's strict analysis and autocompletion.
 * - Handles both console and web response contexts.
 * - Supports dynamic property resolution for console responses.
 *
 * @see PropertiesClassReflectionExtension for custom properties class reflection extension contract.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class ResponsePropertiesClassReflectionExtension implements PropertiesClassReflectionExtension
{
    /**
     * Creates a new instance of the {@see ResponsePropertiesClassReflectionExtension} class.
     *
     * @param ReflectionProvider $reflectionProvider Reflection provider for class and property lookups.
     */
    public function __construct(private readonly ReflectionProvider $reflectionProvider) {}

    /**
     * Retrieves the property reflection for a given property on the Yii console response class by delegating to the web
     * response class.
     *
     * Resolves the property reflection for the specified property name by looking it up on the web response class.
     *
     * This ensures that properties available on the web response are also accessible for static analysis and IDE
     * support on the console response.
     *
     * @param ClassReflection $classReflection Class reflection instance for the Yii console response.
     * @param string $propertyName Name of the property to retrieve.
     *
     * @throws MissingPropertyFromReflectionException if the property doesn't exist on the web response class.
     * @return PropertyReflection Property reflection instance for the specified property.
     */
    public function getProperty(ClassReflection $classReflection, string $propertyName): PropertyReflection
    {
        return $this->reflectionProvider
            ->getClass(\yii\web\Response::class)
            ->getProperty($propertyName, new OutOfClassScope());
    }

    /**
     * Determines whether the specified property exists on the Yii console response class by delegating to the web
     * response class.
     *
     * Checks for the existence of a property on the console response instance by verifying if the corresponding
     * property exists on the web response class.
     *
     * This enables static analysis tools and IDEs to provide accurate autocompletion and type inference for shared
     * response properties.
     *
     * @param ClassReflection $classReflection Class reflection instance for the Yii console response.
     * @param string $propertyName Name of the property to check for existence.
     *
     * @return bool `true` if the property exists on the web response class; `false` otherwise.
     */
    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        if ($classReflection->getName() !== \yii\console\Response::class) {
            return false;
        }

        return $this->reflectionProvider->getClass(\yii\web\Response::class)->hasProperty($propertyName);
    }
}
