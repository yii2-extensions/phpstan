<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\property;

use PHPStan\Analyser\DeclarationDependencyTracker;
use PHPStan\Reflection\Annotations\AnnotationsPropertiesClassReflectionExtension;
use PHPStan\Reflection\{ClassReflection, PropertiesClassReflectionExtension, PropertyReflection, ReflectionProvider};
use PHPStan\Reflection\Dummy\DummyPropertyReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\{ObjectType, ObjectWithoutClassType, Type};
use yii\base\Application;
use yii2\extensions\phpstan\reflection\ComponentPropertyReflection;
use yii2\extensions\phpstan\{ServiceMap, ServiceMapResultCacheValueExtension};

use function is_string;
use function sprintf;

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
     * Creates a new instance of the {@see ApplicationPropertiesClassReflectionExtension} class.
     *
     * @param AnnotationsPropertiesClassReflectionExtension $annotationsProperties Extension for handling
     * annotation-based properties.
     * @param ReflectionProvider $reflectionProvider Reflection provider for class and property lookups.
     * @param ServiceMap $serviceMap Service and component map for Yii Application static analysis.
     * @param DeclarationDependencyTracker $dependencyTracker Records the configuration values each class declaration
     * depends on.
     * @param string[] $genericComponents Optional mapping of component property names to their generic type parameter
     * keys in the component definition.
     */
    public function __construct(
        private readonly AnnotationsPropertiesClassReflectionExtension $annotationsProperties,
        private readonly ReflectionProvider $reflectionProvider,
        private readonly ServiceMap $serviceMap,
        private readonly DeclarationDependencyTracker $dependencyTracker,
        private readonly array $genericComponents = [],
    ) {}

    /**
     * Retrieves the property reflection for a given property on the Yii Application class or its components.
     *
     * Resolves, in order, a native property of the configured application type, a configured component, an
     * annotation-based property, and a configured component whose class can't be determined, typed as `object`.
     * PHPStan calls this method only after {@see hasProperty()} returned `true` for the same class and property name.
     *
     * @param ClassReflection $classReflection Reflection of the class being analyzed.
     * @param string $propertyName Name of the property to resolve.
     *
     * @throws ShouldNotHappenException if the property is neither native, a configured component, nor annotation-based.
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

        if ($this->serviceMap->isUnresolvedComponent($propertyName)) {
            return new ComponentPropertyReflection(
                new DummyPropertyReflection($propertyName),
                new ObjectWithoutClassType(),
                $normalizedClassReflection,
            );
        }

        throw new ShouldNotHappenException(
            sprintf(
                'Property %s::$%s is neither native, a component, nor annotation-based.',
                $normalizedClassReflection->getName(),
                $propertyName,
            ),
        );
    }

    /**
     * Determines whether the specified property exists on the Yii Application class or its components.
     *
     * Applies only to {@see Application} and its subclasses, and checks native properties, dynamic components
     * registered via the service map, annotation-based properties, and components configured with an unknown class,
     * in the same order as {@see getProperty()}.
     *
     * @param ClassReflection $classReflection Reflection of the class being analyzed.
     * @param string $propertyName Name of the property to resolve.
     *
     * @return bool `true` if the property exists as a native, component, or annotated property; `false` otherwise.
     */
    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        if ($classReflection->is(Application::class) === false) {
            return false;
        }

        $this->dependencyTracker->trackValueDependency(
            $classReflection,
            ServiceMapResultCacheValueExtension::class,
            ServiceMapResultCacheValueExtension::APPLICATION_KEY,
        );
        $this->dependencyTracker->trackValueDependency(
            $classReflection,
            ServiceMapResultCacheValueExtension::class,
            ServiceMapResultCacheValueExtension::componentKey($propertyName),
        );

        $configuredApplicationType = $this->serviceMap->getApplicationType();

        if ($configuredApplicationType !== '' && $configuredApplicationType !== $classReflection->getName()) {
            // the answer comes from the configured application class, declared in another file
            $this->dependencyTracker->trackClassDependency($classReflection, $configuredApplicationType);
        }

        $normalizedClassReflection = $this->normalizeClassReflection($classReflection);

        return $normalizedClassReflection->hasNativeProperty($propertyName)
            || $this->serviceMap->getComponentClassById($propertyName) !== null
            || $this->annotationsProperties->hasProperty($normalizedClassReflection, $propertyName)
            || $this->serviceMap->isUnresolvedComponent($propertyName);
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
