<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\reflection;

use PHPStan\Reflection\{
    ClassReflection,
    MissingPropertyFromReflectionException,
    PropertiesClassReflectionExtension,
    PropertyReflection,
};
use PHPStan\Reflection\Annotations\AnnotationsPropertiesClassReflectionExtension;
use PHPStan\Reflection\Dummy\DummyPropertyReflection;
use PHPStan\Type\MixedType;
use yii\web\User;

/**
 * Provides property reflection for a Yii user component in PHPStan analysis.
 *
 * Integrates Yii's {@see User::identity] property and annotation-based property reflection into the user component
 * context, enabling accurate type inference and autocompletion for properties that are available on the user class.
 *
 * This extension allows PHPStan to recognize and reflect the {@see User::identity] property on the Yii user instance,
 * as well as properties defined natively or via annotations, even if they aren't declared as native properties on the
 * user class.
 *
 * The implementation delegates property lookups to annotation-based property extensions and native property reflection,
 * while providing a custom reflection for the dynamic {@see User::identity] property.
 *
 * Key features.
 * - Ensures compatibility with PHPStan's strict analysis and autocompletion.
 * - Integrates annotation-based and native property reflection for the user component.
 * - Provides accurate type inference for the dynamic {@see User::identity] property.
 * - Supports dynamic and annotated property resolution for the user component.
 *
 * @see AnnotationsPropertiesClassReflectionExtension for annotation support.
 * @see ComponentPropertyReflection for dynamic property reflection.
 * @see PropertiesClassReflectionExtension for custom properties class reflection extension contract.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class UserPropertiesClassReflectionExtension implements PropertiesClassReflectionExtension
{
    /**
     * Creates a new instance of the {@see UserPropertiesClassReflectionExtension} class.
     *
     * @param AnnotationsPropertiesClassReflectionExtension $annotationsProperties Extension for handling
     * annotation-based properties.
     */
    public function __construct(private readonly AnnotationsPropertiesClassReflectionExtension $annotationsProperties) {}

    /**
     * Retrieves the property reflection for a given property on the Yii user component.
     *
     * Resolves the property reflection for the specified property name by checking for the dynamic
     * {@see User::identity] property, native properties, and annotation-based properties on the Yii user instance.
     *
     * This method ensures that the {@see User::identity] property and properties defined native or via annotations are
     * accessible for static analysis and IDE support.
     *
     * @param ClassReflection $classReflection Class reflection instance for the Yii user component.
     * @param string $propertyName Name of the property to retrieve.
     *
     * @return PropertyReflection Property reflection instance for the specified property.
     *
     * @throws MissingPropertyFromReflectionException if the property doesn't exist or can't be resolved.
     */
    public function getProperty(ClassReflection $classReflection, string $propertyName): PropertyReflection
    {
        if ($propertyName === 'identity') {
            return new ComponentPropertyReflection(new DummyPropertyReflection($propertyName), new MixedType());
        }

        if ($classReflection->hasNativeProperty($propertyName)) {
            return $classReflection->getNativeProperty($propertyName);
        }

        return $this->annotationsProperties->getProperty($classReflection, $propertyName);
    }

    /**
     * Determines whether the specified property exists on the Yii user component.
     *
     * Checks for the existence of a property on the user instance by considering native properties and
     * annotation-based properties.
     *
     * This method ensures compatibility with the user component context, enabling accurate property reflection for
     * static analysis and IDE autocompletion.
     *
     * @param ClassReflection $classReflection Class reflection instance for the Yii user component.
     * @param string $propertyName Name of the property to check for existence.
     *
     * @return bool `true` if the property exists as a native or annotated property; `false` otherwise.
     */
    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        if ($classReflection->getName() !== User::class) {
            return false;
        }

        return $classReflection->hasNativeProperty($propertyName)
            || $this->annotationsProperties->hasProperty($classReflection, $propertyName);
    }
}
