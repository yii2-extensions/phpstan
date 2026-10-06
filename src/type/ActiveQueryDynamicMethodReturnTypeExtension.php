<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\type;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\{DependencyTracker, Scope};
use PHPStan\Reflection\{MethodReflection, ParametersAcceptorSelector, ReflectionProvider};
use PHPStan\Type\{
    ArrayType,
    DynamicMethodReturnTypeExtension,
    MixedType,
    NeverType,
    ObjectType,
    StringType,
    Type,
    TypeCombinator,
};
use PHPStan\Type\Constant\{ConstantArrayTypeBuilder, ConstantBooleanType, ConstantStringType};
use PHPStan\Type\Generic\GenericObjectType;
use yii\db\{ActiveQuery, ActiveRecord, ActiveRecordInterface};
use yii2\extensions\phpstan\PropertyTagTypeResolver;

use function array_key_exists;
use function array_key_first;
use function count;
use function in_array;
use function strtolower;

/**
 * Infers the row array shape of {@see ActiveQuery::asArray()} queries from the model's class-level `@property` tags.
 *
 * Yii's generic PHPDoc types an `asArray()` query as returning `array<string, mixed>` rows. This extension replaces
 * the query's `T` with an array shape built from the `@property` tags that the queried model declares or inherits, so
 * that subsequent `one()` and `all()` calls return typed rows. A custom query class keeps its own type when it is
 * generic over the model; otherwise, array rows fall back to {@see ActiveQuery} because a non-generic class cannot
 * carry the row shape.
 *
 * A tag whose type, ignoring `null`, is an {@see ActiveRecordInterface} or an iterable of them describes a relation,
 * which a row holds only when it is eager-loaded with `with()`: its key is optional and holds a related row
 * (`array<string, mixed>|null`) or an array of related rows (`array<array<string, mixed>>`). Any other tag keeps its
 * type, as a required key when it is writable and as an optional key when it is read-only. Write-only tags yield no
 * key.
 *
 * `batch()` and `each()` are resolved on `ActiveQuery<T>` when PHPStan's own answer is `never`, as it is for the
 * intersection that {@see ActiveRecordQueryDynamicStaticMethodReturnTypeExtension} infers for a custom query class.
 *
 * A receiver that is a single non-generic {@see ActiveQuery} subclass whose `T` names no model, such as a Gii query
 * class reached through a typed parameter, a typed property, a returned value, or a scope returning `self`, has its
 * model derived from the nearest `one()` and `all()` declarations below {@see ActiveQuery}. When their return types
 * name exactly one Active Record subclass, ignoring arrays, `null`, and the Active Record base classes that Yii's
 * unbound `T` yields, `one()`, `all()`, `batch()`, and `each()` are resolved on `ActiveQuery<Model>`, and `asArray()`
 * builds the model's row shape. Otherwise, PHPStan's own answer is kept.
 *
 * {@see ActiveQuery} for Active Query API details.
 * {@see PropertyTagTypeResolver} for the collection of declared and inherited `@property` tags.
 * {@see DynamicMethodReturnTypeExtension} for PHPStan dynamic return type extension contract.
 */
final class ActiveQueryDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    /**
     * Model class names derived from the `one()` and `all()` declarations, indexed by lowercase query class name, with
     * `null` for a query class from which no model is derived.
     *
     * @var array<string, string|null>
     */
    private array $derivedModelClassNames = [];

    /**
     * @param ReflectionProvider $reflectionProvider Reflection provider for query and model class lookups.
     * @param PropertyTagTypeResolver $propertyTagTypeResolver Resolver of the model's declared and inherited
     * `@property` tags.
     */
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
        private readonly PropertyTagTypeResolver $propertyTagTypeResolver,
    ) {}

    /**
     * Returns the Yii Active Query class supported by this extension.
     *
     * @phpstan-return class-string
     */
    public function getClass(): string
    {
        return ActiveQuery::class;
    }

    /**
     * Resolves the query type returned by {@see ActiveQuery::asArray()} from the receiver and the `$value` argument.
     *
     * Calls to `one()`, `all()`, `batch()`, and `each()` are resolved on `ActiveQuery<Model>` when the model is derived
     * from the receiver's query class; otherwise, `batch()` and `each()` are delegated to
     * {@see createBatchQueryResultType()}.
     *
     * `true` (or no argument) yields rows shaped by the model's `@property` tags, `false` returns the receiver
     * unchanged, and a non-constant value yields the union of both. When `T` is already an array, `asArray()` keeps it
     * unchanged, as Yii's own `T is array ? static<T>` branch does. A nullable receiver, as in a nullsafe chain, is
     * read without `null`, which PHPStan adds back for the short-circuited call.
     *
     * @param MethodReflection $methodReflection Reflection of the called `asArray()`, `one()`, `all()`, `batch()`, or
     * `each()` method.
     * @param MethodCall $methodCall Method call with arguments already normalized by PHPStan.
     * @param DependencyTracker&Scope $scope Current PHPStan analysis scope, recording a dependency on a derived model.
     *
     * @return Type|null Inferred query, row, or batch query result type, or `null` to defer to PHPStan's answer when an
     * `asArray()` argument is unpacked, no model is derived for `one()` and `all()`, or `batch()` and `each()` already
     * resolve.
     */
    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type|null {
        $methodName = $methodReflection->getName();

        $calledOnType = TypeCombinator::removeNull($scope->getType($methodCall->var));

        $modelType = $this->resolveDerivedModelType($calledOnType, $scope);

        if ($methodName !== 'asArray') {
            if ($modelType !== null) {
                return $this->resolveReturnType(
                    new GenericObjectType(ActiveQuery::class, [$modelType]),
                    $methodName,
                    $methodCall,
                    $scope,
                );
            }

            return in_array($methodName, ['batch', 'each'], true)
                ? $this->createBatchQueryResultType($methodReflection, $methodCall, $scope)
                : null;
        }

        $args = $methodCall->getArgs();

        foreach ($args as $arg) {
            if ($arg->unpack) {
                return null;
            }
        }

        $valueType = isset($args[0])
            ? $scope->getType($args[0]->value)
            : new ConstantBooleanType(true);

        if ($valueType->isFalse()->yes()) {
            return $calledOnType;
        }

        $rowType = $modelType ?? $calledOnType->getTemplateType(ActiveQuery::class, 'T');
        $arrayRowType = $this->createArrayRowType($rowType);

        return $this->createQueryType(
            $calledOnType,
            $valueType->isTrue()->yes() ? $arrayRowType : TypeCombinator::union($rowType, $arrayRowType),
        );
    }

    /**
     * Returns whether the reflected method is {@see ActiveQuery::asArray()}, {@see ActiveQuery::one()},
     * {@see ActiveQuery::all()}, {@see ActiveQuery::batch()}, or {@see ActiveQuery::each()}.
     */
    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return in_array($methodReflection->getName(), ['all', 'asArray', 'batch', 'each', 'one'], true);
    }

    /**
     * Builds the array row type for the given query row type.
     *
     * @param Type $rowType Current `T` of the query.
     *
     * @return Type `T` itself when it is already an array, an array shape from the declared and inherited `@property`
     * tags of the single model class, or `array<string, mixed>` when no single model or no readable tags are available.
     */
    private function createArrayRowType(Type $rowType): Type
    {
        if ($rowType->isArray()->yes()) {
            return $rowType;
        }

        $genericArrayType = new ArrayType(new StringType(), new MixedType());

        $modelClassNames = $rowType->getObjectClassNames();

        if (count($modelClassNames) !== 1 || $this->reflectionProvider->hasClass($modelClassNames[0]) === false) {
            return $genericArrayType;
        }

        $modelReflection = $this->reflectionProvider->getClass($modelClassNames[0]);
        $readableTags = $this->propertyTagTypeResolver->getReadableTags($modelReflection);

        if ($readableTags === []) {
            return $genericArrayType;
        }

        $arrayShapeBuilder = ConstantArrayTypeBuilder::createEmpty();

        foreach ($readableTags as $name => ['type' => $tagType, 'writable' => $writable]) {
            [$valueType, $optional] = $this->createRowValueType($tagType, $writable, $genericArrayType);

            $arrayShapeBuilder->setOffsetValueType(new ConstantStringType($name), $valueType, $optional);
        }

        return $arrayShapeBuilder->getArray();
    }

    /**
     * Resolves the batch query result returned by {@see ActiveQuery::batch()} or {@see ActiveQuery::each()} when
     * PHPStan's own answer is `never`.
     *
     * A query type inferred by {@see ActiveRecordQueryDynamicStaticMethodReturnTypeExtension} intersects a custom query
     * class with `ActiveQuery<Model>`, and PHPStan intersects the two invariant `BatchQueryResult` return types into
     * `never`. The method is then resolved on `ActiveQuery<T>` with the receiver's `T`, which yields the same result as
     * on a query generic over the model. Any other answer is kept.
     *
     * @param MethodReflection $methodReflection Reflection of the called `batch()` or `each()` method.
     * @param MethodCall $methodCall Method call whose receiver provides the row type.
     * @param Scope $scope Current PHPStan analysis scope.
     *
     * @return Type|null Batch query result type, or `null` to keep PHPStan's answer.
     */
    private function createBatchQueryResultType(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type|null {
        $args = $methodCall->getArgs();

        $neverType = new NeverType();

        $returnType = ParametersAcceptorSelector::selectFromArgs(
            $scope,
            $args,
            $methodReflection->getVariants(),
        )->getReturnType();

        if ($neverType->isSuperTypeOf($returnType)->yes() === false) {
            return null;
        }

        $rowType = TypeCombinator::removeNull($scope->getType($methodCall->var))
            ->getTemplateType(ActiveQuery::class, 'T');

        if ($neverType->isSuperTypeOf($rowType)->yes()) {
            return null;
        }

        return $this->resolveReturnType(
            new GenericObjectType(ActiveQuery::class, [$rowType]),
            $methodReflection->getName(),
            $methodCall,
            $scope,
        );
    }

    /**
     * Returns the receiver's query class with `T` replaced by the given row type.
     *
     * The receiver is returned unchanged when its `T` already equals `$rowType`. A query class with a single template
     * parameter is parameterized with `$rowType` when that parameter resolves to `T`; otherwise (for example, a
     * non-generic subclass of `ActiveQuery<Model>`), the result is `ActiveQuery<$rowType>`.
     *
     * @param Type $calledOnType Type of the query on which `asArray()` is called.
     * @param Type $rowType Row type that the resulting query yields.
     *
     * @return Type Query type yielding `$rowType` rows.
     */
    private function createQueryType(Type $calledOnType, Type $rowType): Type
    {
        if ($calledOnType->getTemplateType(ActiveQuery::class, 'T')->equals($rowType)) {
            return $calledOnType;
        }

        $queryClassNames = $calledOnType->getObjectClassNames();

        if (count($queryClassNames) === 1 && $this->reflectionProvider->hasClass($queryClassNames[0])) {
            $queryReflection = $this->reflectionProvider->getClass($queryClassNames[0]);

            if (count($queryReflection->getTemplateTags()) === 1) {
                $queryType = new GenericObjectType($queryReflection->getName(), [$rowType]);

                if ($queryType->getTemplateType(ActiveQuery::class, 'T')->equals($rowType)) {
                    return $queryType;
                }
            }
        }

        return new GenericObjectType(ActiveQuery::class, [$rowType]);
    }

    /**
     * Returns the row value type of a `@property` tag and whether its row key is optional.
     *
     * A type that, ignoring `null`, is certainly an {@see ActiveRecordInterface} is read as a to-one relation and
     * yields `$relatedRowType` or `null`; an iterable whose value type, ignoring `null`, is certainly one is read as a
     * to-many relation and yields an array of `$relatedRowType`, which `indexBy()` may key by any value. Both keys are
     * optional, because a relation is present only when it is eager-loaded. Any other tag keeps its type, and its key
     * is optional only when the tag is read-only.
     *
     * @param Type $tagType Readable type of the tag.
     * @param bool $writable Whether the tag is writable, that is, declared with `@property` rather than its `-read`
     * variant.
     * @param Type $relatedRowType Type of a single related row.
     *
     * @return array{Type, bool} Row value type, and `true` when the row key is optional.
     */
    private function createRowValueType(Type $tagType, bool $writable, Type $relatedRowType): array
    {
        $type = TypeCombinator::removeNull($tagType);

        if (
            $type->isIterable()->yes()
            && $this->isActiveRecordType(TypeCombinator::removeNull($type->getIterableValueType()))
        ) {
            return [new ArrayType(new MixedType(), $relatedRowType), true];
        }

        if ($this->isActiveRecordType($type)) {
            return [TypeCombinator::addNull($relatedRowType), true];
        }

        return [$tagType, $writable === false];
    }

    /**
     * Derives the model class from the nearest `one()` and `all()` declarations of a non-generic query class.
     *
     * Declarations inherited from {@see ActiveQuery} or its parents are skipped. The return type of `one()` names its
     * object classes, ignoring arrays and `null`; the return type of `all()` names the object classes of its array
     * values. A named Active Record subclass is a candidate, {@see ActiveRecord} and its parents are ignored as the
     * types that Yii's unbound `T` yields, and any other class prevents the derivation.
     *
     * @param string $queryClassName Name of the query class to read the declarations from.
     *
     * @return string|null Name of the single candidate model class, or `null` when the query class is unknown,
     * generic, or not an {@see ActiveQuery} subclass, or when its declarations name no candidate, several candidates,
     * or another class.
     */
    private function deriveModelClassName(string $queryClassName): string|null
    {
        if ($this->reflectionProvider->hasClass($queryClassName) === false) {
            return null;
        }

        $queryReflection = $this->reflectionProvider->getClass($queryClassName);
        $activeQueryReflection = $this->reflectionProvider->getClass(ActiveQuery::class);

        if ($queryReflection->isGeneric() || $queryReflection->isSubclassOfClass($activeQueryReflection) === false) {
            return null;
        }

        $activeRecordReflection = $this->reflectionProvider->getClass(ActiveRecord::class);
        $modelClassNames = [];

        foreach (['all', 'one'] as $methodName) {
            if ($queryReflection->hasNativeMethod($methodName) === false) {
                continue;
            }

            $methodReflection = $queryReflection->getNativeMethod($methodName);

            if ($methodReflection->getDeclaringClass()->isSubclassOfClass($activeQueryReflection) === false) {
                continue;
            }

            foreach ($methodReflection->getVariants() as $variant) {
                foreach ($this->getReturnedClassNames($methodName, $variant->getReturnType()) as $className) {
                    if ($this->reflectionProvider->hasClass($className) === false) {
                        return null;
                    }

                    $classReflection = $this->reflectionProvider->getClass($className);

                    if ($classReflection->isSubclassOfClass($activeRecordReflection)) {
                        $modelClassNames[$classReflection->getName()] = true;
                    } elseif (
                        $classReflection->getName() !== ActiveRecord::class
                        && $activeRecordReflection->isSubclassOfClass($classReflection) === false
                    ) {
                        return null;
                    }
                }
            }
        }

        return count($modelClassNames) === 1 ? array_key_first($modelClassNames) : null;
    }

    /**
     * Returns the object class names that a `one()` or `all()` return type names.
     *
     * @param string $methodName Name of the declared method, `one` or `all`.
     * @param Type $returnType Declared return type of the method.
     *
     * @return list<string> Object class names of the row type for `one()`, or of the array value types for `all()`.
     */
    private function getReturnedClassNames(string $methodName, Type $returnType): array
    {
        if ($methodName === 'one') {
            return $this->getRowClassNames($returnType);
        }

        $classNames = [];

        foreach (TypeCombinator::removeNull($returnType)->getArrays() as $arrayType) {
            foreach ($this->getRowClassNames($arrayType->getIterableValueType()) as $className) {
                $classNames[] = $className;
            }
        }

        return $classNames;
    }

    /**
     * Returns the object class names that a row type names once arrays and `null` are removed.
     *
     * @param Type $rowType Row type declared by `one()`, or array value type declared by `all()`.
     *
     * @return list<string> Object class names, or an empty list when the remaining type is not certainly an object.
     */
    private function getRowClassNames(Type $rowType): array
    {
        return TypeCombinator::remove(
            TypeCombinator::removeNull($rowType),
            new ArrayType(new MixedType(), new MixedType()),
        )->getObjectClassNames();
    }

    /**
     * Returns whether the type is certainly an {@see ActiveRecordInterface} object.
     *
     * The {@see Type::isObject()} check rejects the `never` type that remains of a `null`-only tag, which every type
     * would otherwise accept as a subtype.
     *
     * @param Type $type Type to check, without `null`.
     */
    private function isActiveRecordType(Type $type): bool
    {
        return $type->isObject()->yes() && (new ObjectType(ActiveRecordInterface::class))->isSuperTypeOf($type)->yes();
    }

    /**
     * Returns the model type derived from the receiver's query class, when the receiver's `T` names no model.
     *
     * The receiver must be a single class other than {@see ActiveQuery} itself, so that a bound query, a union, and the
     * intersection inferred by {@see ActiveRecordQueryDynamicStaticMethodReturnTypeExtension} keep their own answer.
     * The derivation is cached per query class; the dependency on the derived model is recorded on every call, because
     * the receiver names the model only in the query class PHPDoc.
     *
     * @param Type $calledOnType Receiver type without `null`.
     * @param DependencyTracker&Scope $scope Scope recording a dependency on the derived model class.
     *
     * @return ObjectType|null Derived model type, or `null` when no model is derived.
     */
    private function resolveDerivedModelType(Type $calledOnType, Scope $scope): ObjectType|null
    {
        $queryClassNames = $calledOnType->getObjectClassNames();

        if (count($queryClassNames) !== 1 || strtolower($queryClassNames[0]) === strtolower(ActiveQuery::class)) {
            return null;
        }

        $unboundType = $calledOnType->getTemplateType(ActiveQuery::class, 'T');

        if ($unboundType->isSuperTypeOf(new ObjectType(ActiveRecord::class))->yes() === false) {
            return null;
        }

        $cacheKey = strtolower($queryClassNames[0]);

        if (array_key_exists($cacheKey, $this->derivedModelClassNames) === false) {
            $this->derivedModelClassNames[$cacheKey] = $this->deriveModelClassName($queryClassNames[0]);
        }

        $modelClassName = $this->derivedModelClassNames[$cacheKey];

        if ($modelClassName === null) {
            return null;
        }

        $scope->trackClassDependency($modelClassName);

        return new ObjectType($modelClassName);
    }

    /**
     * Returns the return type of a method called with the given arguments on a query type.
     *
     * @param Type $queryType Query type on which the method is resolved.
     * @param string $methodName Name of the called method.
     * @param MethodCall $methodCall Method call providing the arguments.
     * @param Scope $scope Current PHPStan analysis scope.
     *
     * @return Type|null Return type of the method, or `null` when the query type has no such method.
     */
    private function resolveReturnType(
        Type $queryType,
        string $methodName,
        MethodCall $methodCall,
        Scope $scope,
    ): Type|null {
        $methodReflection = $scope->getMethodReflection($queryType, $methodName);

        if ($methodReflection === null) {
            return null;
        }

        return ParametersAcceptorSelector::selectFromArgs(
            $scope,
            $methodCall->getArgs(),
            $methodReflection->getVariants(),
        )->getReturnType();
    }
}
