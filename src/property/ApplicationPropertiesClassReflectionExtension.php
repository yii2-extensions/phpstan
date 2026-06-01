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
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\{MixedType, ObjectType, Type};
use yii\base\Application;
use yii2\extensions\phpstan\reflection\ComponentPropertyReflection;
use yii2\extensions\phpstan\ServiceMap;

use function in_array;
use function is_string;

/**
 * Resolves dynamic component properties on the Yii Application instance for PHPStan analysis.
 *
 * Recognizes properties defined via configuration, dependency injection, or service mapping on base, web, and console
 * application contexts, even when not declared natively. Delegates to annotation-based and native property reflection,
 * and resolves component types (including generics) through the {@see ServiceMap}.
 *
 * {@see \yii\base\Application} for Yii Base Application class.
 * {@see \yii\console\Application} for Yii Console Application class.
 * {@see \yii\web\Application} for Yii Web Application class.
 * {@see PropertiesClassReflectionExtension} for custom properties class reflection extension contract.
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
     */
    private const SUPPORTED_APPLICATION_CLASSES = [
        Application::class,
        \yii\console\Application::class,
        \yii\web\Application::class,
    ];

    /**
     * Creates a new instance of the {@see ApplicationPropertiesClassReflectionExtension} class.
     *
     * @param AnnotationsPropertiesClassReflectionExtension $annotationsProperties Extension for handling
     * annotation-based properties.
     * @param ReflectionProvider $reflectionProvider Reflection provider for class and property lookups.
     * @param ServiceMap $serviceMap Service and component map for Yii Application static analysis.
     * @param string[] $genericComponents Optional mapping of component property names to their generic type parameter
     * keys in the component definition.
     */
    public function __construct(
        private readonly AnnotationsPropertiesClassReflectionExtension $annotationsProperties,
        private readonly ReflectionProvider $reflectionProvider,
        private readonly ServiceMap $serviceMap,
        private readonly array $genericComponents = [],
    ) {}

    /**
     * Retrieves the property reflection for a given property on the Yii Application class or its components.
     *
     * Resolves the property reflection for the specified property name by checking for dynamic components, native
     * properties, and annotation-based properties on the Yii Application instance.
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
                $this->resolveType($componentClass, $propertyName),
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

        if ($this->reflectionProvider->hasClass(Application::class)) {
            return $classReflection->isSubclassOfClass(
                $this->reflectionProvider->getClass(Application::class),
            );
        }

        return false;
    }

    /**
     * Normalizes the class reflection for Yii Application subclasses to ensure consistent property resolution.
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

    /**
     * Resolves the PHPStan type for a Yii Application component property, including generic type support.
     *
     * Determines the appropriate {@see Type} for the specified component class and property name by inspecting the
     * generic component mapping and the component definition.
     *
     * If a generic type is defined and present in the component definition, returns a {@see GenericObjectType} with the
     * resolved type parameter; otherwise, returns a standard {@see ObjectType} for the component class.
     *
     * This enables accurate type inference for application components that use generics in their configuration,
     * supporting precise static analysis and autocompletion in PHPStan.
     *
     * @param string $componentClass Fully qualified class name of the component.
     * @param string $propertyName Name of the property being resolved.
     *
     * @return Type Resolved PHPStan type for the component property, including generics if available.
     */
    private function resolveType(string $componentClass, string $propertyName): Type
    {
        $genericProperty = $this->genericComponents[$propertyName] ?? null;
        $componentDefinition = $this->serviceMap->getComponentDefinitionById($propertyName);

        if ($componentDefinition !== [] && $genericProperty !== null) {
            $genericType = $componentDefinition[$genericProperty] ?? null;

            if (is_string($genericType) && $genericType !== '') {
                return new GenericObjectType($componentClass, [new ObjectType($genericType)]);
            }
        }

        return new ObjectType($componentClass);
    }
}
