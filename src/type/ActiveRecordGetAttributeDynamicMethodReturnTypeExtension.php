<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\type;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\{DependencyTracker, Scope};
use PHPStan\Reflection\{MethodReflection, ReflectionProvider};
use PHPStan\Type\{DynamicMethodReturnTypeExtension, MixedType, Type};
use yii\db\ActiveRecord;
use yii2\extensions\phpstan\{PropertyTagTypeResolver, ServiceMap, ServiceMapResultCacheValueExtension};

use function count;

/**
 * Infers return types for {@see ActiveRecord::getAttribute()} calls in PHPStan analysis.
 *
 * Examines the constant string argument passed to {@see ActiveRecord::getAttribute()} and resolves the corresponding
 * property type from the `@property` tags that the model declares or inherits, falling back to behaviors registered
 * through the {@see ServiceMap}, and finally to {@see MixedType} for unknown or non-constant attribute names and for
 * unpacked arguments.
 *
 * {@see ActiveRecord} for Active Record API details.
 * {@see DynamicMethodReturnTypeExtension} for PHPStan dynamic return type extension contract.
 * {@see PropertyTagTypeResolver} for the collection of declared and inherited `@property` tags.
 * {@see ServiceMap} for service and component map for Yii Application static analysis.
 */
final class ActiveRecordGetAttributeDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    /**
     * Creates a new instance of the {@see ActiveRecordGetAttributeDynamicMethodReturnTypeExtension} class.
     *
     * @param ReflectionProvider $reflectionProvider Reflection provider for class and property lookups.
     * @param ServiceMap $serviceMap Service and component map for Yii Application static analysis.
     * @param PropertyTagTypeResolver $propertyTagTypeResolver Resolver of declared and inherited `@property` tags.
     */
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
        private readonly ServiceMap $serviceMap,
        private readonly PropertyTagTypeResolver $propertyTagTypeResolver,
    ) {}

    /**
     * Returns the class name for which this dynamic method return type extension applies.
     *
     * Specifies the fully qualified class name of the supported class, enabling PHPStan to associate this extension
     * with method calls on the {@see ActiveRecord} base class and its subclasses.
     *
     * @return string Fully qualified class name of the supported {@see ActiveRecord} class.
     *
     * @phpstan-return class-string
     */
    public function getClass(): string
    {
        return ActiveRecord::class;
    }

    /**
     * Infers the return type for {@see ActiveRecord::getAttribute()} method calls.
     *
     * Resolves the return type for {@see ActiveRecord::getAttribute()} method by analyzing the attribute name argument
     * and extracting type information from PHPDoc property annotations declared or inherited by the model class and its
     * attached behaviors.
     *
     * This enables precise type inference for static analysis and IDE autocompletion when accessing ActiveRecord
     * attributes dynamically.
     *
     * @param MethodReflection $methodReflection Reflection instance for the method being analyzed.
     * @param MethodCall $methodCall AST node for the method call expression.
     * @param Scope&DependencyTracker $scope PHPStan analysis scope for type resolution.
     *
     * @return Type Inferred return type for the {@see ActiveRecord::getAttribute()} call, or {@see MixedType} if the
     * attribute type can't be determined.
     */
    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type {
        $args = $methodCall->getArgs();

        foreach ($args as $arg) {
            if ($arg->unpack) {
                return new MixedType();
            }
        }

        if (isset($args[0]) === false) {
            return new MixedType();
        }

        $constantStrings = $scope->getType($args[0]->value)->getConstantStrings();

        if (count($constantStrings) !== 1) {
            return new MixedType();
        }

        $attributeName = $constantStrings[0]->getValue();
        $calledOnType = $scope->getType($methodCall->var);
        $classNames = $calledOnType->getObjectClassNames();

        if (count($classNames) !== 1) {
            return new MixedType();
        }

        $className = $classNames[0];

        if ($this->reflectionProvider->hasClass($className) === false) {
            return new MixedType();
        }

        $propertyType = $this->propertyTagTypeResolver->getReadableType(
            $this->reflectionProvider->getClass($className),
            $attributeName,
        );

        if ($propertyType !== null) {
            return $propertyType;
        }

        $scope->trackValueDependency(
            ServiceMapResultCacheValueExtension::class,
            ServiceMapResultCacheValueExtension::behaviorsKey($className),
        );

        $propertyType = $this->getPropertyTypeFromBehaviors($className, $attributeName, $scope);

        return $propertyType ?? new MixedType();
    }

    /**
     * Checks if the given method is supported for dynamic return type inference.
     *
     * Determines support by verifying if the method name is {@see ActiveRecord::getAttribute()}.
     *
     * This ensures that only the {@see ActiveRecord::getAttribute()} method with dynamic return types is handled by
     * this extension for precise type inference during static analysis.
     *
     * @param MethodReflection $methodReflection Reflection instance for the method being analyzed.
     *
     * @return bool `true` if the method is {@see ActiveRecord::getAttribute()}; `false` otherwise.
     */
    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'getAttribute';
    }

    /**
     * Searches for property types in attached behaviors' PHPDoc annotations.
     *
     * Iterates through all behaviors registered for the specified model class via the {@see ServiceMap} and examines
     * their PHPDoc property annotations to locate the requested attribute type.
     *
     * This method provides comprehensive type resolution by extending the search beyond the model class itself to
     * include properties defined in attached behaviors, enabling accurate type inference for dynamic attributes that
     * are provided by behaviors rather than the model directly.
     *
     * The search process validates each behavior class existence, creates reflection instances, and delegates to
     * {@see PropertyTagTypeResolver::getReadableType()} for the tags that each behavior class declares or inherits.
     *
     * @param string $className Fully qualified class name to check.
     * @param string $attributeName The attribute name to search for.
     * @param Scope&DependencyTracker $scope Scope recording a dependency on each behavior class consulted.
     *
     * @return Type|null Property type if found in any behavior, `null` if not found or behavior classes are
     * unavailable.
     */
    private function getPropertyTypeFromBehaviors(
        string $className,
        string $attributeName,
        Scope $scope,
    ): Type|null {
        $behaviors = $this->serviceMap->getBehaviorsByClassName($className);

        foreach ($behaviors as $behaviorClass) {
            $scope->trackClassDependency($behaviorClass);

            if ($this->reflectionProvider->hasClass($behaviorClass)) {
                $behaviorReflection = $this->reflectionProvider->getClass($behaviorClass);

                $propertyType = $this->propertyTagTypeResolver->getReadableType($behaviorReflection, $attributeName);

                if ($propertyType !== null) {
                    return $propertyType;
                }
            }
        }

        return null;
    }
}
