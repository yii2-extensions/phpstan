<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\type;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\{ClassReflection, MethodReflection, ReflectionProvider};
use PHPStan\Type\{DynamicMethodReturnTypeExtension, FileTypeMapper, MixedType, Type};
use Throwable;
use yii\db\ActiveRecord;
use yii2\extensions\phpstan\ServiceMap;

use function count;

/**
 * Infers return types for {@see ActiveRecord::getAttribute()} calls in PHPStan analysis.
 *
 * Examines the constant string argument passed to {@see ActiveRecord::getAttribute()} and resolves the corresponding
 * property type from the model's PHPDoc `@property` tags, falling back to behaviors registered through the
 * {@see ServiceMap}, and finally to {@see MixedType} for unknown or non-constant attribute names.
 *
 * {@see ActiveRecord} for Active Record API details.
 * {@see DynamicMethodReturnTypeExtension} for PHPStan dynamic return type extension contract.
 * {@see ServiceMap} for service and component map for Yii Application static analysis.
 */
final class ActiveRecordGetAttributeDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    /**
     * Creates a new instance of the {@see ActiveRecordGetAttributeDynamicMethodReturnTypeExtension} class.
     *
     * @param ReflectionProvider $reflectionProvider Reflection provider for class and property lookups.
     * @param FileTypeMapper $fileTypeMapper File type mapper for resolving PHPDoc types.
     * @param ServiceMap $serviceMap Service and component map for Yii Application static analysis.
     */
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
        private readonly FileTypeMapper $fileTypeMapper,
        private readonly ServiceMap $serviceMap,
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
     * and extracting type information from PHPDoc property annotations in the model class and its attached behaviors.
     *
     * This enables precise type inference for static analysis and IDE autocompletion when accessing ActiveRecord
     * attributes dynamically.
     *
     * @param MethodReflection $methodReflection Reflection instance for the method being analyzed.
     * @param MethodCall $methodCall AST node for the method call expression.
     * @param Scope $scope PHPStan analysis scope for type resolution.
     *
     * @return Type Inferred return type for the {@see ActiveRecord::getAttribute()} call, or {@see MixedType} if the
     * attribute type can't be determined.
     */
    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type {
        if (isset($methodCall->args[0]) === false || $methodCall->args[0]::class !== Arg::class) {
            return new MixedType();
        }

        $argType = $scope->getType($methodCall->args[0]->value);
        $constantStrings = $argType->getConstantStrings();

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

        $classReflection = $this->reflectionProvider->getClass($className);
        $propertyType = $this->getPropertyTypeFromPhpDoc($classReflection, $attributeName);

        if ($propertyType !== null) {
            return $propertyType;
        }

        $propertyType = $this->getPropertyTypeFromBehaviors($className, $attributeName);

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
     * {@see getPropertyTypeFromPhpDoc()} for actual property type extraction from behavior PHPDoc blocks.
     *
     * @param string $className Fully qualified class name to check.
     * @param string $attributeName The attribute name to search for.
     *
     * @return Type|null Property type if found in any behavior, `null` if not found or behavior classes are
     * unavailable.
     */
    private function getPropertyTypeFromBehaviors(string $className, string $attributeName): Type|null
    {
        $behaviors = $this->serviceMap->getBehaviorsByClassName($className);

        foreach ($behaviors as $behaviorClass) {
            if ($this->reflectionProvider->hasClass($behaviorClass)) {
                $behaviorReflection = $this->reflectionProvider->getClass($behaviorClass);

                $propertyType = $this->getPropertyTypeFromPhpDoc($behaviorReflection, $attributeName);

                if ($propertyType !== null) {
                    return $propertyType;
                }
            }
        }

        return null;
    }

    /**
     * Extracts the type of a specific property from PHPDoc annotations.
     *
     * Parses the PHPDoc comment of the provided {@see ClassReflection} to retrieve property tags and their associated
     * types for the specified property name.
     *
     * This enables precise type inference for model properties in static analysis by examining the `@property` tags
     * documented in the class's PHPDoc block and resolving their types using the file type mapper.
     *
     * Only properties explicitly documented in the PHPDoc block with `@property` tags are considered for type
     * extraction.
     *
     * @param ClassReflection $classReflection Reflection of the class being analyzed.
     * @param string $propertyName Name of the property to resolve.
     *
     * @return Type|null Resolved property type if found in PHPDoc annotations, `null` if not available or extraction
     * fails.
     */
    private function getPropertyTypeFromPhpDoc(ClassReflection $classReflection, string $propertyName): Type|null
    {
        $fileName = $classReflection->getFileName();

        if ($fileName === null) {
            return null;
        }

        $docComment = $classReflection->getNativeReflection()->getDocComment();

        if ($docComment === false) {
            return null;
        }

        try {
            $resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
                $fileName,
                $classReflection->getName(),
                null,
                null,
                docComment: $docComment,
            );

            $propertyTags = $resolvedPhpDoc->getPropertyTags();

            if (isset($propertyTags[$propertyName])) {
                return $propertyTags[$propertyName]->getReadableType();
            }

            return null;
        } catch (Throwable) {
            return null;
        }
    }
}
