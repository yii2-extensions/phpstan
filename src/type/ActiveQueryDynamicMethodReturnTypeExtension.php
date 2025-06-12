<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\type;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\{ClassReflection, MethodReflection, ReflectionProvider};
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\{
    ArrayType,
    IntegerType,
    MixedType,
    NullType,
    StringType,
    ThisType,
    Type,
    TypeCombinator
};
use PHPStan\Type\Constant\{ConstantArrayType, ConstantBooleanType, ConstantStringType};
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\Generic\GenericObjectType;
use Throwable;
use yii\db\ActiveQuery;

use function count;
use function in_array;
use function sprintf;

/**
 * Provides dynamic return type extension for Yii Active Query methods in PHPStan analysis.
 *
 * Implements dynamic return type resolution for the {@see ActiveQuery} class, enabling PHPStan to infer precise return
 * types for the {@see ActiveQuery::one()}, {@see ActiveQuery::all()}, and {@see ActiveQuery::asArray()} methods based
 * on the model type and method arguments.
 *
 * This extension analyzes the generic type parameter of {@see ActiveQuery} to determine the model type, and inspects
 * method arguments to resolve array or object return types for {@see ActiveQuery::asArray()}.
 *
 * It also supports union and array types for {@see ActiveQuery::one()} and {@see ActiveQuery::all()} respectively, and
 * preserves the generic type for fluent interface methods.
 *
 * Key features:
 * - Array shape inference from PHPDoc property tags for model classes.
 * - Dynamic return type inference for {@see ActiveQuery::one()}, {@see ActiveQuery::all()}, and
 *   {@see ActiveQuery::asArray()} methods.
 * - Exception handling for invalid method arguments.
 * - Extraction of model type from generic {@see ActiveQuery} instances.
 * - Integration with PHPStan's type combinators and file type mapper for accurate analysis.
 * - Support for union, array, and generic object types in method return values.
 *
 * @see ActiveQuery for query API details.
 * @see DynamicMethodReturnTypeExtension for PHPStan dynamic return type extension contract.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class ActiveQueryDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    /**
     * Supported methods for dynamic return type resolution.
     *
     * @phpstan-var string[]
     */
    private const SUPPORTED_METHODS = ['one', 'all', 'asArray'];

    /**
     * Creates a new instance of the {@see ActiveQueryDynamicMethodReturnTypeExtension} class.
     *
     * @param ReflectionProvider $reflectionProvider Reflection provider for class and property lookups.
     * @param FileTypeMapper $fileTypeMapper File type mapper for resolving PHPDoc types.
     */
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
        private readonly FileTypeMapper $fileTypeMapper,
    ) {}

    /**
     * Returns the class name for which this dynamic method return type extension applies.
     *
     * Specifies the fully qualified class name of the supported class, enabling PHPStan to associate this extension
     * with method calls on the {@see ActiveQuery} base class and its subclasses.
     *
     * This method is essential for registering the extension with PHPStan's type system, ensuring that static method
     * return type inference is applied to the correct class hierarchy during static analysis and IDE autocompletion.
     *
     * @return string Fully qualified class name of the supported {@see ActiveQuery} class.
     *
     * @phpstan-return class-string
     */
    public function getClass(): string
    {
        return ActiveQuery::class;
    }

    /**
     * Infers the return type for a method call on an {@see ActiveQuery} instance based on method name and arguments.
     *
     * Determines the correct return type for {@see ActiveQuery::asArray()}, {@see ActiveQuery::one()}, and
     * {@see ActiveQuery::all()} methods by inspecting the method arguments and runtime context, enabling accurate type
     * inference for static analysis and IDE support.
     *
     * @param MethodReflection $methodReflection Reflection instance for the called method.
     * @param MethodCall $methodCall AST node for the method call expression.
     * @param Scope $scope PHPStan analysis scope for type resolution.
     *
     * @throws ShouldNotHappenException if the method call context or arguments are invalid.
     *
     * @return Type Inferred return type for the method call.
     */
    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type {
        $calledOnType = $scope->getType($methodCall->var);
        $modelType = $this->extractModelType($calledOnType);
        $methodName = $methodReflection->getName();

        return match ($methodName) {
            'asArray' => $this->handleAsArray($methodCall, $scope, $modelType),
            'one' => TypeCombinator::union(new NullType(), $modelType),
            'all' => new ArrayType(new IntegerType(), $modelType),
            default => $this->handleDefaultCase($methodReflection, $calledOnType, $modelType),
        };
    }

    /**
     * Determines whether the given method is supported for dynamic return type inference.
     *
     * Checks if the method returns `$this` or is one of the supported methods {@see ActiveQuery::one()}, and
     * {@see ActiveQuery::all()}.
     *
     * @param MethodReflection $methodReflection Reflection instance for the method.
     *
     * @return bool `true` if the method is supported for dynamic return type inference; `false` otherwise.
     */
    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        if (in_array($methodReflection->getName(), self::SUPPORTED_METHODS, true)) {
            return true;
        }

        $variants = $methodReflection->getVariants();

        if (count($variants) > 0) {
            $returnType = $variants[0]->getReturnType();

            return $returnType::class === ThisType::class;
        }

        return false;
    }

    /**
     * Creates a constant array type from a set of property types for array shape inference.
     *
     * Constructs a {@see ConstantArrayType} instance representing an array shape, where each key corresponds to a
     * property name and each value to its associated type.
     *
     * This is used to provide precise array type inference for model properties when analyzing {@see ActiveQuery}
     * results with {@see ActiveQuery::asArray()} in PHPStan static analysis.
     *
     * The method iterates over the provided property types, mapping each property name to a {@see ConstantStringType}
     * key and its type to the corresponding value.
     *
     * The resulting {@see ConstantArrayType} enables accurate type checking and autocompletion for associative arrays
     * returned by {@see ActiveQuery} methods.
     *
     * @param array $properties Map of property names to their types.
     *
     * @return ConstantArrayType Array shape type representing the model's properties.
     *
     * @phpstan-param array<string, Type> $properties
     */
    private function createConstantArrayType(array $properties): ConstantArrayType
    {
        $keyTypes = [];
        $valueTypes = [];

        foreach ($properties as $propertyName => $propertyType) {
            $keyTypes[] = new ConstantStringType($propertyName);
            $valueTypes[] = $propertyType;
        }

        return new ConstantArrayType($keyTypes, $valueTypes);
    }

    /**
     * Creates a generic array type with string keys and mixed values for model property inference.
     *
     * Constructs an {@see ArrayType} instance representing an associative array with string keys and mixed values.
     *
     * This method is used as a fallback when the model type is unknown or when property extraction from PHPDoc fails,
     * ensuring that static analysis and IDE autocompletion provide a safe, general array type for {@see ActiveQuery}
     * results.
     *
     * @return ArrayType Generic array type with string keys and mixed values.
     */
    private function createGenericArrayType(): ArrayType
    {
        return new ArrayType(new StringType(), new MixedType());
    }

    /**
     * Extracts the model type from a {@see GenericObjectType} instance of {@see ActiveQuery}.
     *
     * Determines the generic type parameter representing the model class for the given {@see ActiveQuery} instance.
     *
     * If the provided type is a generic {@see ActiveQuery}, it returns the first generic type argument as the model
     * type; otherwise, it returns {@see MixedType} as a fallback.
     *
     * This method is essential for enabling precise type inference in dynamic return type extensions, allowing PHPStan
     * to resolve the model type used in {@see ActiveQuery} method calls such as {@see ActiveQuery::one()},
     * {@see ActiveQuery::all()}, and {@see ActiveQuery::asArray()}.
     *
     * @param Type $calledOnType Type on which the method is called, expected to be a {@see GenericObjectType} of
     * {@see ActiveQuery}.
     *
     * @return Type Extracted model type if available, or {@see MixedType} if not resolvable.
     */
    private function extractModelType(Type $calledOnType): Type
    {
        if ($calledOnType::class === GenericObjectType::class && $calledOnType->getClassName() === ActiveQuery::class) {
            $types = $calledOnType->getTypes();

            return $types[0] ?? new MixedType();
        }

        return new MixedType();
    }

    /**
     * Extracts property types from the PHPDoc block of a given class reflection.
     *
     * Parses the PHPDoc comment of the provided {@see ClassReflection} to retrieve property tags and their associated
     * types, enabling array shape inference for model classes in static analysis.
     *
     * This method is used to support precise type inference for associative arrays returned by {@see ActiveQuery}
     * methods when the {@see ActiveQuery::asArray()} method is used, allowing PHPStan to provide accurate type checking
     * and autocompletion for model properties based on documented PHPDoc annotations.
     *
     * @param ClassReflection $classReflection Class reflection instance for the model.
     *
     * @return array Associative array of property names to their types, or an empty array if not available.
     *
     * @phpstan-return array<string, Type>
     */
    private function extractPropertiesFromPhpDoc(ClassReflection $classReflection): array
    {
        $fileName = $classReflection->getFileName();

        if ($fileName === null) {
            return [];
        }

        $docComment = $classReflection->getNativeReflection()->getDocComment();

        if ($docComment === false) {
            return [];
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

            if (count($propertyTags) === 0) {
                return [];
            }

            $properties = [];

            foreach ($propertyTags as $propertyName => $propertyTag) {
                $readablePropertyType = $propertyTag->getReadableType();

                if ($readablePropertyType !== null) {
                    $properties[$propertyName] = $readablePropertyType;
                }
            }

            return $properties;
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Infers an array shape type from the model's PHPDoc property annotations for static analysis.
     *
     * Constructs a {@see ConstantArrayType} representing the array shape of the model's properties, based on PHPDoc
     * property tags extracted from the model class.
     *
     * If the model type is unknown, not a single class, or property extraction fails, a generic associative array type
     * is returned as a fallback.
     *
     * This method enables precise type inference for associative arrays returned by {@see ActiveQuery} methods such as
     * {@see ActiveQuery::asArray()}, allowing PHPStan to provide accurate type checking and autocompletion for model
     * properties in array results.
     *
     * @param Type $modelType Model type extracted from the generic {@see ActiveQuery} instance.
     *
     * @return Type A {@see ConstantArrayType} representing the model's array shape, or a generic associative array type
     * if property extraction is impossible.
     */
    private function getArrayTypeFromModelProperties(Type $modelType): Type
    {
        if ($modelType::class === MixedType::class) {
            return $this->createGenericArrayType();
        }

        $objectClassNames = $modelType->getObjectClassNames();

        if (count($objectClassNames) !== 1) {
            return $this->createGenericArrayType();
        }

        $className = $objectClassNames[0];

        if ($this->reflectionProvider->hasClass($className) === false) {
            return $this->createGenericArrayType();
        }

        $properties = $this->extractPropertiesFromPhpDoc($this->reflectionProvider->getClass($className));

        if (count($properties) === 0) {
            return $this->createGenericArrayType();
        }

        return $this->createConstantArrayType($properties);
    }

    /**
     * Retrieves the argument type for the {@see ActiveQuery::asArray()} method call.
     *
     * Determines the type of the first argument passed to the {@see ActiveQuery::asArray()} method, enabling dynamic
     * return type inference based on whether the argument is present and its value.
     *
     * If the argument is not provided or is not an instance of {@see Arg}, the method returns a constant boolean
     * type representing `true` as the default behavior for {@see ActiveQuery::asArray()} in Yii Active Query.
     *
     * The dynamic return type extension uses this method internally to resolve the correct return type for
     * {@see ActiveQuery::asArray()} calls during static analysis.
     *
     * @param MethodCall $methodCall AST node for the method call expression.
     * @param Scope $scope PHPStan analysis scope for type resolution.
     *
     * @return Type Type of the first argument if present, or {@see ConstantBooleanType} `true` if not provided.
     */
    private function getAsArrayArgument(MethodCall $methodCall, Scope $scope): Type
    {
        if (isset($methodCall->args[0]) === false || ($methodCall->args[0]::class === Arg::class) === false) {
            return new ConstantBooleanType(true);
        }

        return $scope->getType($methodCall->args[0]->value);
    }

    /**
     * Infers the return type for the {@see ActiveQuery::asArray()} method call based on the provided argument.
     *
     * Determines the resulting generic type for the {@see ActiveQuery} instance by analyzing the argument passed to
     * {@see ActiveQuery::asArray()}.
     *
     * - If the argument is `true` (or omitted), the return type is an {@see ActiveQuery} with an array shape type
     * inferred from the model's PHPDoc properties.
     * - If the argument is `false`, the return type is an {@see ActiveQuery} with the original model type.
     *
     * This method enables precise static analysis and autocompletion for chained {@see asArray()} calls, ensuring that
     * the correct type is inferred for later method calls on the {@see ActiveQuery} instance.
     *
     * @param MethodCall $methodCall AST node for the {@see asArray()} method call expression.
     * @param Scope $scope PHPStan analysis scope for type resolution.
     * @param Type $modelType Model type extracted from the generic {@see ActiveQuery} instance.
     *
     * @throws ShouldNotHappenException if the method call context or arguments are invalid.
     *
     * @return Type Inferred generic {@see ActiveQuery} type with either an array shape or the original model type.
     */
    private function handleAsArray(MethodCall $methodCall, Scope $scope, Type $modelType): Type
    {
        $argType = $this->getAsArrayArgument($methodCall, $scope);

        if ($argType->isTrue()->yes()) {
            return new GenericObjectType(ActiveQuery::class, [$this->getArrayTypeFromModelProperties($modelType)]);
        }

        if ($argType->isFalse()->yes()) {
            return new GenericObjectType(ActiveQuery::class, [$modelType]);
        }

        throw new ShouldNotHappenException(
            sprintf("Invalid argument provided to 'asArray' method at line '%d'", $methodCall->getLine()),
        );
    }

    /**
     * Handles the default case for dynamic return type inference in {@see ActiveQuery} methods.
     *
     * Determines the return type for methods that aren't explicitly handled by other logic, such as fluent interface
     * methods returning `$this`.
     *
     * If the method's return type is {@see ThisType}, it preserves the generic model type for the {@see ActiveQuery}
     * instance; otherwise, it returns the type on which the method was called.
     *
     * This method ensures that chained method calls on {@see ActiveQuery} maintain accurate type information for static
     * analysis and IDE autocompletion, supporting fluent interface patterns and generic type propagation.
     *
     * @param MethodReflection $methodReflection Reflection instance for the called method.
     * @param Type $calledOnType Type on which the method is called, expected to be a {@see GenericObjectType} of
     * {@see ActiveQuery}.
     * @param Type $modelType Model type extracted from the generic {@see ActiveQuery} instance.
     *
     * @return Type Inferred return type for the method call, preserving the generic model type for fluent methods or
     * returning the original called-on type otherwise.
     */
    private function handleDefaultCase(MethodReflection $methodReflection, Type $calledOnType, Type $modelType): Type
    {
        $variants = $methodReflection->getVariants();

        if (count($variants) > 0) {
            $returnType = $variants[0]->getReturnType();

            if ($returnType::class === ThisType::class) {
                return $this->preserveModelType($calledOnType, $modelType);
            }
        }

        return $calledOnType;
    }

    /**
     * Preserves the generic model type for {@see ActiveQuery} fluent interface methods.
     *
     * Ensures that chained method calls on {@see ActiveQuery} maintain accurate generic type information for static
     * analysis and IDE autocompletion.
     *
     * - If the called-on type is a generic {@see ActiveQuery} instance, it is returned as-is to preserve the model
     *   type.
     * - If the model type is not {@see MixedType}, a new generic {@see ActiveQuery} type is constructed with the
     *   provided model type; otherwise, the original called-on type is returned as a fallback.
     *
     * This method is used internally by the dynamic return type extension to support fluent interface patterns and
     * generic type propagation in {@see ActiveQuery} method chains.
     *
     * @param Type $calledOnType Type on which the method is called, expected to be a {@see GenericObjectType} of
     * {@see ActiveQuery}.
     * @param Type $modelType Extracted model type from the generic {@see ActiveQuery} instance.
     *
     * @return Type Preserved generic {@see ActiveQuery} type with the correct model type, or the original type if
     * preservation is impossible.
     */
    private function preserveModelType(Type $calledOnType, Type $modelType): Type
    {
        if ($calledOnType::class === GenericObjectType::class && $calledOnType->getClassName() === ActiveQuery::class) {
            return $calledOnType;
        }

        if ($modelType::class !== MixedType::class) {
            return new GenericObjectType(ActiveQuery::class, [$modelType]);
        }

        return $calledOnType;
    }
}
