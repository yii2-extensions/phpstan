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
use PHPStan\Type\{
    BooleanType,
    IntegerType,
    ObjectType,
    StringType,
    TypeCombinator,
};
use yii\web\User;
use yii2\extensions\phpstan\ServiceMap;

/**
 * Provides property reflection for a Yii user component in PHPStan analysis.
 *
 * Integrates Yii's {@see User::identity] property and annotation-based property reflection into the user component
 * context, enabling accurate type inference and autocompletion for properties that are available on the user class.
 *
 * This extension allows PHPStan to recognize and reflect the {@see User::identity} property on the Yii user instance,
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
     * @param ReflectionProvider $reflectionProvider Reflection provider for class and property lookups.
     * @param ServiceMap $serviceMap Service map for resolving component classes by ID.
     */
    public function __construct(
        private readonly AnnotationsPropertiesClassReflectionExtension $annotationsProperties,
        private readonly ReflectionProvider $reflectionProvider,
        private readonly ServiceMap $serviceMap,
    ) {}

    /**
     * Retrieves the property reflection for a given property on the Yii user component.
     *
     * Resolves the property reflection for the specified property name by checking for the dynamic
     * {@see User::identity} property, native properties, and annotation-based properties on the Yii user instance.
     *
     * For the 'identity' property, it resolves the type based on the configured identityClass in the user component.
     *
     * @param ClassReflection $classReflection Class reflection instance for the Yii user component.
     * @param string $propertyName Name of the property to retrieve.
     *
     * @throws MissingPropertyFromReflectionException if the property doesn't exist or can't be resolved.
     *
     * @return PropertyReflection Property reflection instance for the specified property.
     */
    public function getProperty(ClassReflection $classReflection, string $propertyName): PropertyReflection
    {
        if (in_array($propertyName, ['id', 'identity', 'isGuest'], true) === true) {
            $identityClass = $this->getIdentityClass();

            if ($propertyName === 'identity' && $identityClass !== null) {
                return new ComponentPropertyReflection(
                    new DummyPropertyReflection($propertyName),
                    new ObjectType($identityClass),
                    $classReflection,
                );
            }

            if ($propertyName === 'id') {
                return new ComponentPropertyReflection(
                    new DummyPropertyReflection($propertyName),
                    TypeCombinator::union(new IntegerType(), new StringType()),
                    $classReflection,
                );
            }

            if ($propertyName === 'isGuest') {
                return new ComponentPropertyReflection(
                    new DummyPropertyReflection($propertyName),
                    new BooleanType(),
                    $classReflection,
                );
            }

            if (($componentClass = $this->serviceMap->getComponentClassById($propertyName)) !== null) {
                return new ComponentPropertyReflection(
                    new DummyPropertyReflection($propertyName),
                    new ObjectType($componentClass),
                    $classReflection,
                );
            }
        }

        if ($classReflection->hasNativeProperty($propertyName)) {
            return $classReflection->getNativeProperty($propertyName);
        }

        return $this->annotationsProperties->getProperty($classReflection, $propertyName);
    }

    /**
     * Determines whether the specified property exists on the Yii user component.
     *
     * Checks for the existence of a property on the user instance by considering native properties,
     * annotation-based properties, and the special 'identity' property.
     *
     * @param ClassReflection $classReflection Class reflection instance for the Yii user component.
     * @param string $propertyName Name of the property to check for existence.
     *
     * @return bool `true` if the property exists as a native, annotated, or identity property; `false` otherwise.
     */
    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        if (
            $classReflection->getName() !== User::class &&
            $classReflection->isSubclassOfClass($this->reflectionProvider->getClass(User::class)) === false
        ) {
            return false;
        }

        return
            $this->getIdentityClass() !== null ||
            $this->serviceMap->getComponentClassById($propertyName) !== null;
    }

    /**
     * Attempts to resolve the identity class from the user component configuration.
     *
     * This method tries to determine the identityClass configured for the user component
     * by looking at the service map's user component configuration.
     *
     * @return string|null The fully qualified identity class name, or null if not found.
     */
    private function getIdentityClass(): string|null
    {
        $identityClass = null;

        $definition = $this->serviceMap->getComponentDefinitionByClassName(User::class);

        if (isset($definition['identityClass']) && is_string($definition['identityClass'])) {
            $identityClass = $definition['identityClass'];
        }

        return $identityClass;
    }
}
