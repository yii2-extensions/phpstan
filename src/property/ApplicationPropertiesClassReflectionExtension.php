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
use yii2\extensions\phpstan\exception\Message;
use yii2\extensions\phpstan\reflection\{ComponentPropertyReflection, ReceiverPropertyReflection};
use yii2\extensions\phpstan\{ServiceMap, ServiceMapResultCacheValueExtension};

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
     * Resolves, in order, a native property of the associated application class, a configured component, an
     * annotation-based property, and a configured component whose class can't be determined, typed as `object`. Such a
     * component takes precedence over the annotation, since its definition decides the value, unless it's an array
     * without a class key, which Yii completes from the core component the annotation describes. A class unrelated to
     * the configured application type resolves only its own native and annotated members. PHPStan calls this method
     * only after {@see hasProperty()} returned `true` for the same class and property name.
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
        $associatedClassReflection = $this->resolveAssociatedClassReflection($classReflection);
        $normalizedClassReflection = $associatedClassReflection ?? $classReflection;

        if ($normalizedClassReflection->hasNativeProperty($propertyName)) {
            return $normalizedClassReflection->getNativeProperty($propertyName);
        }

        if (
            $associatedClassReflection !== null
            && null !== $componentClass = $this->serviceMap->getComponentClassById($propertyName)
        ) {
            return new ComponentPropertyReflection(
                new DummyPropertyReflection($propertyName),
                $this->resolveType($componentClass, $propertyName),
                $normalizedClassReflection,
            );
        }

        $isUnresolved = $associatedClassReflection !== null && $this->serviceMap->isUnresolvedComponent($propertyName);

        // a configured definition decides the value, except an array without a class key, which Yii completes from the
        // core components that the application class annotates
        if (
            ($isUnresolved === false || $this->serviceMap->isComponentWithoutClass($propertyName))
            && $this->annotationsProperties->hasProperty($normalizedClassReflection, $propertyName)
        ) {
            $property = $this->annotationsProperties->getProperty($normalizedClassReflection, $propertyName);

            // PHPStan resolves an inherited property again on the ancestor declaring it, which may be refined to the
            // configured application type, so an unrelated application declares the property itself
            if (
                $associatedClassReflection === null
                && $property->getDeclaringClass()->getName() !== $classReflection->getName()
            ) {
                return new ReceiverPropertyReflection($property, $classReflection);
            }

            return $property;
        }

        if ($isUnresolved) {
            return new ComponentPropertyReflection(
                new DummyPropertyReflection($propertyName),
                new ObjectWithoutClassType(),
                $normalizedClassReflection,
            );
        }

        throw new ShouldNotHappenException(
            Message::PROPERTY_NOT_RESOLVED->getMessage($normalizedClassReflection->getName(), $propertyName),
        );
    }

    /**
     * Determines whether the specified property exists on the Yii Application class or its components.
     *
     * Applies only to {@see Application} and its subclasses, and checks native properties, dynamic components
     * registered via the service map, annotation-based properties, and components configured with an unknown class,
     * in the same order as {@see getProperty()}. Configured components apply only to a class associated with the
     * configured application type; an unrelated application class checks only its own native and annotated members.
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

        $associatedClassReflection = $this->resolveAssociatedClassReflection($classReflection);

        if ($associatedClassReflection === null) {
            // an unrelated application keeps its own members and never receives the configured components
            return $classReflection->hasNativeProperty($propertyName)
                || $this->annotationsProperties->hasProperty($classReflection, $propertyName);
        }

        return $associatedClassReflection->hasNativeProperty($propertyName)
            || $this->serviceMap->getComponentClassById($propertyName) !== null
            || $this->annotationsProperties->hasProperty($associatedClassReflection, $propertyName)
            || $this->serviceMap->isUnresolvedComponent($propertyName);
    }

    /**
     * Resolves the class reflection the configured application's members and components apply to.
     *
     * An ancestor of the configured application type, such as {@see Application} or `yii\web\Application` for a custom
     * Web application, is refined to the configured type, since a single entry point is analyzed. The configured type
     * itself and its subclasses keep their own reflection, so their native and annotated members and their generic
     * substitutions aren't replaced by a broader class. Any other application, such as a console application under a
     * Web description, is unrelated and receives neither the configured members nor the configured components.
     *
     * @param ClassReflection $classReflection Reflection of the class being analyzed.
     *
     * @return ClassReflection|null Class reflection associated with the configured application type, or `null` if the
     * class is unrelated to it or the configured application type can't be reflected.
     */
    private function resolveAssociatedClassReflection(ClassReflection $classReflection): ClassReflection|null
    {
        $configuredApplicationType = $this->serviceMap->getApplicationType();

        if ($this->reflectionProvider->hasClass($configuredApplicationType) === false) {
            return null;
        }

        $configuredClassReflection = $this->reflectionProvider->getClass($configuredApplicationType);

        if (
            $classReflection->getName() === $configuredClassReflection->getName()
            || $classReflection->isSubclassOfClass($configuredClassReflection)
        ) {
            return $classReflection;
        }

        if ($configuredClassReflection->isSubclassOfClass($classReflection)) {
            return $configuredClassReflection;
        }

        return null;
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
