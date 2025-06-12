<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\type;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\{ClassReflection, MethodReflection, ReflectionProvider};
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

/**
 * Provides dynamic return type extension for Yii Active Query methods in PHPStan analysis.
 *
 * Enables PHPStan to infer precise return types for {@see ActiveQuery} methods such as {@see ActiveQuery::one()},
 * {@see ActiveQuery::all()}, and {@see ActiveQuery::asArray()} based on the model type and method arguments.
 *
 * This extension analyzes the generic type parameter of {@see ActiveQuery} to determine the model type and inspects
 * method arguments to resolve array or object return types for {@see ActiveQuery::asArray()}.
 *
 * It supports union and array types for {@see ActiveQuery::one()} and {@see ActiveQuery::all()} respectively, and
 * preserves the generic type for fluent interface methods.
 *
 * Key features.
 * - Array shape inference from PHPDoc property tags for model classes.
 * - Dynamic return type inference for {@see ActiveQuery::one()}, {@see ActiveQuery::all()}, and
 *   {@see ActiveQuery::asArray()} methods.
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
     * Infers the return type for a method call on an {@see ActiveQuery} instance.
     *
     * Resolves the return type for {@see ActiveQuery::all()}, {@see ActiveQuery::one()}, and
     * {@see ActiveQuery::asArray()} methods by analyzing the model type and method context.
     *
     * This enables precise type inference for static analysis and IDE autocompletion.
     *
     * The method inspects the called method name and delegates to specialized handlers for supported methods,
     * returning.
     * - For {@see ActiveQuery::all()}: an array of the model type indexed by integer.
     * - For {@see ActiveQuery::asArray()}: the result of {@see handleAsArray()} with the current model type.
     * - For {@see ActiveQuery::one()}: a union of the model type and null.
     * - For other methods: the result of {@see handleDefaultCase()}.
     *
     * @param MethodReflection $methodReflection Reflection for the called method.
     * @param MethodCall $methodCall AST node for the method call expression.
     * @param Scope $scope PHPStan analysis scope for type resolution.
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
            'all' => new ArrayType(new IntegerType(), $modelType),
            'asArray' => $this->handleAsArray($methodCall, $scope, $modelType),
            'one' => TypeCombinator::union(new NullType(), $modelType),
            default => $this->handleDefaultCase($methodReflection, $calledOnType, $modelType),
        };
    }

    /**
     * Checks if the given method is supported for dynamic return type inference.
     *
     * Determines support by verifying if the method name is in {@see self::SUPPORTED_METHODS} or if the first variant's
     * return type is {@see ThisType}.
     *
     * This ensures that only methods with dynamic return types or fluent interfaces are handled by this extension.
     *
     * @param MethodReflection $methodReflection Reflection instance for the method being analyzed.
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
     * Constructs a {@see ConstantArrayType} representing an array shape for model property types.
     *
     * Iterates over the provided property map, creating a constant array type where each key is a property name and
     * each value is its associated type.
     *
     * Used for precise array shape inference in {@see ActiveQuery} results with {@see ActiveQuery::asArray()} during
     * static analysis.
     *
     * @param array<string, Type> $properties Map of property names to their types.
     *
     * @return ConstantArrayType Array shape type for the model's properties.
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
     * Returns a generic array type with string keys and mixed values.
     *
     * Provides a fallback associative array type for {@see ActiveQuery} results when the model type can't be determined
     * or property extraction from PHPDoc is unavailable.
     *
     * This ensures static analysis and IDE autocompletion remain safe and general for dynamic query scenarios.
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
     * Resolves the generic type parameter representing the model class for the given {@see ActiveQuery} instance.
     *
     * If the provided type is a generic {@see ActiveQuery}, returns the first generic type argument as the model type;
     * otherwise, returns {@see MixedType} as a fallback.
     *
     * This method enables precise type inference for dynamic return type extensions, allowing PHPStan to determine the
     * model type used in {@see ActiveQuery} method calls such as {@see ActiveQuery::one()}, {@see ActiveQuery::all()},
     * and {@see ActiveQuery::asArray()}.
     *
     * @param Type $calledOnType Type on which the method is called. Expected to be a {@see GenericObjectType} of
     * {@see ActiveQuery} or its subclass.
     *
     * @return Type The extracted model type if available, or {@see MixedType} if not resolvable.
     */
    private function extractModelType(Type $calledOnType): Type
    {
        if ($calledOnType::class === GenericObjectType::class) {
            $className = $calledOnType->getClassName();

            if ($className === ActiveQuery::class) {
                $types = $calledOnType->getTypes();

                return $types[0] ?? new MixedType();
            }

            if ($this->reflectionProvider->hasClass($className)) {
                $classReflection = $this->reflectionProvider->getClass($className);

                if ($classReflection->isSubclassOfClass($this->reflectionProvider->getClass(ActiveQuery::class))) {
                    $types = $calledOnType->getTypes();

                    return $types[0] ?? new MixedType();
                }
            }
        }

        return new MixedType();
    }

    /**
     * Extracts property types from the PHPDoc block of the given class reflection.
     *
     * Parses the PHPDoc comment of the provided {@see ClassReflection} to retrieve property tags and their associated
     * types.
     *
     * This enables array shape inference for model classes in static analysis, supporting precise type inference for
     * associative arrays returned by {@see ActiveQuery} methods when {@see ActiveQuery::asArray()} is used.
     *
     * Only properties explicitly documented in the PHPDoc block are considered.
     *
     * If the file name or doc comment is unavailable, or if no property tags are found, an empty array is returned.
     *
     * @param ClassReflection $classReflection Class reflection instance for the model.
     *
     * @return array<string, Type> Associative array of property names to their types, or an empty array if not
     * available.
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
     * Extracts the query class name from the provided type for dynamic return type inference.
     *
     * If the given type is a {@see GenericObjectType}, returns its class name; otherwise, returns the base
     * {@see ActiveQuery} class name.
     *
     * This method is used to determine the correct query class for type analysis and ensures compatibility with
     * subclasses of {@see ActiveQuery} during static analysis.
     *
     * @param Type $calledOnType Type on which the method is called.
     *
     * @return string Fully qualified class name of the query object.
     */
    private function extractQueryClass(Type $calledOnType): string
    {
        if ($calledOnType::class === GenericObjectType::class) {
            return $calledOnType->getClassName();
        }

        return ActiveQuery::class;
    }

    /**
     * Infers the array shape type for a model from its PHPDoc property annotations.
     *
     * Examines the provided model type and attempts to extract property types from its PHPDoc block using reflection.
     *
     * If the model type is not a single class is unknown, or property extraction fails, a generic associative array
     * type is returned.
     *
     * This method is used to provide accurate array shape types for associative arrays returned by {@see ActiveQuery}
     * methods such as {@see ActiveQuery::asArray()} in static analysis, enabling precise type checking and
     * autocompletion for model properties.
     *
     * @param Type $modelType Model type extracted from the generic {@see ActiveQuery} instance.
     *
     * @return Type {@see ConstantArrayType} for the model's array shape, or a generic associative array type if
     * extraction fails.
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
     * Returns the type of the first argument passed to {@see ActiveQuery::asArray()} for static analysis.
     *
     * Determines the type of the first argument provided to the {@see ActiveQuery::asArray()} method call.
     *
     * If no argument is given or the argument is not an instance of {@see Arg}, this method returns a
     * {@see ConstantBooleanType} representing `true`, which is the default behavior for {@see ActiveQuery::asArray()}
     * in Yii Active Query.
     *
     * This method is used internally by the dynamic return type extension to support accurate type inference for
     * {@see ActiveQuery::asArray()} calls during static analysis.
     *
     * @param MethodCall $methodCall AST node for the {@see asArray()} method call.
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
     * Infers the return type for the {@see ActiveQuery::asArray()} method call based on the argument value.
     *
     * Determines the resulting generic type for the {@see ActiveQuery} instance by analyzing the argument passed to
     * {@see ActiveQuery::asArray()}.
     *
     * - If the argument is `true` (or omitted), returns a {@see GenericObjectType} for the query class with an array
     *   shape type inferred from the model's PHPDoc properties.
     * - If the argument is `false`, returns a {@see GenericObjectType} for the query class with the original model
     *   type.
     * - If the argument is dynamic or unknown, returns a {@see GenericObjectType} for the query class with a union of
     *   the model type and the array shape type.
     *
     * This method enables precise type inference for chained {@see asArray()} calls, ensuring that the correct type is
     * propagated for subsequent method calls on the {@see ActiveQuery} instance during static analysis.
     *
     * @param MethodCall $methodCall AST node for the {@see asArray()} method call.
     * @param Scope $scope PHPStan analysis scope for type resolution.
     * @param Type $modelType Model type extracted from the generic {@see ActiveQuery} instance.
     *
     * @return Type Inferred {@see GenericObjectType} for the query class with the appropriate generic type argument.
     */
    private function handleAsArray(MethodCall $methodCall, Scope $scope, Type $modelType): Type
    {
        $calledOnType = $scope->getType($methodCall->var);
        $queryClass = $this->extractQueryClass($calledOnType);
        $argType = $this->getAsArrayArgument($methodCall, $scope);

        if ($argType->isTrue()->yes()) {
            return new GenericObjectType($queryClass, [$this->getArrayTypeFromModelProperties($modelType)]);
        }

        if ($argType->isFalse()->yes()) {
            return new GenericObjectType($queryClass, [$modelType]);
        }

        return new GenericObjectType(
            $queryClass,
            [
                TypeCombinator::union($modelType, $this->getArrayTypeFromModelProperties($modelType)),
            ],
        );
    }

    /**
     * Returns the inferred return type for ActiveQuery methods not handled by specific logic.
     *
     * If the method's return type is {@see ThisType}, preserves the generic model type for the {@see ActiveQuery}
     * instance; otherwise, returns the type on which the method is called.
     *
     * This ensures that fluent interface methods maintain correct generic type propagation for static analysis and IDE
     * support.
     *
     * @param MethodReflection $methodReflection Reflection for the called method.
     * @param Type $calledOnType Type on which the method is called.
     * @param Type $modelType Model type extracted from the generic {@see ActiveQuery} instance.
     *
     * @return Type Inferred return type, preserving the generic model type for fluent methods or returning the original
     * type.
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
     * Preserves the generic model type for fluent interface methods on {@see ActiveQuery}.
     *
     * Returns the original {@see GenericObjectType} if present, ensuring that chained method calls on
     * {@see ActiveQuery} maintain the correct generic type for static analysis and IDE autocompletion.
     *
     * If the called-on type is not a {@see GenericObjectType} but the model type is not {@see MixedType}, constructs a
     * new {@see GenericObjectType} for {@see ActiveQuery} with the provided model type; otherwise, returns the original
     * type.
     *
     * This method is used internally to support fluent interface patterns and generic type propagation in dynamic
     * return type extension logic.
     *
     * @param Type $calledOnType The type on which the method is called.
     * @param Type $modelType The extracted model type from the generic {@see ActiveQuery} instance.
     *
     * @return Type The preserved generic {@see ActiveQuery} type with the correct model type, or the original type if
     * preservation is not possible.
     */
    private function preserveModelType(Type $calledOnType, Type $modelType): Type
    {
        if ($calledOnType::class === GenericObjectType::class) {
            return $calledOnType;
        }

        if ($modelType::class !== MixedType::class) {
            return new GenericObjectType(ActiveQuery::class, [$modelType]);
        }

        return $calledOnType;
    }
}
