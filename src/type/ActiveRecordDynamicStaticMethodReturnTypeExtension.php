<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\type;

use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\{MethodReflection, ParametersAcceptorSelector, ReflectionProvider};
use PHPStan\Type\{
    DynamicStaticMethodReturnTypeExtension,
    NullType,
    ObjectType,
    ThisType,
    Type,
    TypeCombinator,
    UnionType,
};
use PHPStan\Type\Generic\GenericObjectType;
use yii\db\{ActiveQuery, ActiveRecord};

use function count;

/**
 * Provides dynamic return type extension for Yii Active Record static methods in PHPStan analysis.
 *
 * Integrates Yii Active Record with PHPStan dynamic static method return type extension system, enabling precise type
 * inference for static {@see ActiveRecord} methods such as {@see ActiveRecord::find()}, {@see ActiveRecord::findOne()},
 * and custom static query methods.
 *
 * This extension analyzes the static method's return type and the called class context to determine the most accurate
 * return type, including model type preservation for generic queries, nullability for single-record fetches, and
 * generic {@see ActiveQuery} wrapping.
 *
 * The implementation supports:
 * - Accurate return type inference for static ActiveRecord methods returning {@see ThisType}, {@see UnionType},
 *   {@see ActiveQuery}, or custom {@see ActiveQuery} subclasses.
 * - Compatibility with PHPStan strict static analysis and autocompletion.
 * - Model type preservation for generic {@see ActiveQuery} results.
 * - Nullability handling for methods like {@see ActiveRecord::findOne()}.
 * - Support for custom {@see ActiveQuery} subclasses and generic parameter propagation.
 *
 * @see ActiveQuery for query API details.
 * @see ActiveRecord for ActiveRecord API details.
 * @see DynamicStaticMethodReturnTypeExtension for PHPStan dynamic static method return type extension contract.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class ActiveRecordDynamicStaticMethodReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{
    /**
     * Creates a new instance of the {@see ActiveQueryObjectType} class.
     *
     * @param ReflectionProvider $reflectionProvider Reflection provider for class and property lookups.
     */
    public function __construct(private readonly ReflectionProvider $reflectionProvider) {}

    /**
     * Returns the class name for which this dynamic static method return type extension applies.
     *
     * Specifies the fully qualified class name of the supported class, enabling PHPStan to associate this extension
     * with static method calls on the {@see ActiveRecord} base class and its subclasses.
     *
     * This method is essential for registering the extension with PHPStan type system, ensuring that static method
     * return type inference is applied to the correct class hierarchy during static analysis and IDE autocompletion.
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
     * Determines whether the specified static method is supported for dynamic return type inference.
     *
     * Inspects the method's return type and class context to decide if the static method should be handled by this
     * extension for dynamic return type analysis.
     *
     * This includes methods returning `$this`, union types containing {@see ActiveRecord} subclasses, or types related
     * to {@see ActiveQuery}.
     *
     * This method enables PHPStan to apply custom type inference for static {@see ActiveRecord} query methods ensuring
     * accurate autocompletion and static analysis for methods such as {@see ActiveRecord::findOne()},
     * {@see ActiveRecord::findAll()}, and custom query builders.
     *
     * @param MethodReflection $methodReflection Reflection instance for the static method.
     *
     * @return bool `true` if the static method is supported for dynamic return type inference; `false` otherwise.
     */
    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        $variants = $methodReflection->getVariants();

        if (count($variants) === 0) {
            return false;
        }

        $returnType = $variants[0]->getReturnType();

        if ($returnType::class === ThisType::class) {
            return true;
        }

        if ($returnType::class === UnionType::class) {
            foreach ($returnType->getTypes() as $type) {
                $classNames = $type->getObjectClassNames();

                if (count($classNames) > 0) {
                    $className = $classNames[0];

                    if ($this->reflectionProvider->hasClass($className)) {
                        $classReflection = $this->reflectionProvider->getClass($className);

                        return $classReflection->isSubclassOfClass(
                            $this->reflectionProvider->getClass($this->getClass()),
                        );
                    }
                }
            }
        }

        $classNames = $returnType->getObjectClassNames();

        if (count($classNames) > 0) {
            $className = $classNames[0];

            if ($className === ActiveQuery::class) {
                return true;
            }

            if ($this->reflectionProvider->hasClass($className)) {
                $classReflection = $this->reflectionProvider->getClass($className);

                return $classReflection->isSubclassOfClass($this->reflectionProvider->getClass(ActiveQuery::class));
            }
        }

        return $returnType::class === GenericObjectType::class && $returnType->getClassName() === ActiveQuery::class;
    }

    /**
     * Infers the return type for a static method call on an {@see ActiveRecord} class based on method signature and
     * context.
     *
     * Determines the correct return type for static {@see ActiveRecord} query methods by analyzing the method's
     * declared return type and the class context of the static call.
     *
     * This enables PHPStan to provide accurate type inference and autocompletion for methods such as
     * {@see ActiveRecord::findOne()}, {@see ActiveRecord::findAll()}, and custom query builders.
     *
     * This method ensures that static analysis and IDEs reflect the actual runtime behavior of static
     * {@see ActiveRecord} methods, supporting precise type checks and developer productivity.
     *
     * @param MethodReflection $methodReflection Reflection instance for the static method.
     * @param StaticCall $methodCall AST node for the static method call expression.
     * @param Scope $scope PHPStan analysis scope for type resolution.
     *
     * @return Type Inferred return type for the static method call.
     */
    public function getTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        Scope $scope,
    ): Type {
        $className = $methodCall->class;

        $returnType = ParametersAcceptorSelector::selectFromArgs(
            $scope,
            $methodCall->getArgs(),
            $methodReflection->getVariants(),
        )->getReturnType();

        if ($className::class !== Name::class) {
            return $returnType;
        }

        $name = $scope->resolveName($className);
        $modelType = new ObjectType($name);

        if ($returnType::class === ThisType::class) {
            return $modelType;
        }

        if ($returnType::class === UnionType::class) {
            $hasNull = false;
            $hasActiveRecord = false;

            foreach ($returnType->getTypes() as $type) {
                if ($type::class === NullType::class) {
                    $hasNull = true;
                }

                $classNames = $type->getObjectClassNames();

                if (count($classNames) > 0 && $this->isActiveRecordClass($classNames[0])) {
                    $hasActiveRecord = true;
                }
            }

            if ($hasNull && $hasActiveRecord) {
                return TypeCombinator::union(new NullType(), $modelType);
            }
        }

        $classNames = $returnType->getObjectClassNames();

        if (count($classNames) > 0 && $classNames[0] === ActiveQuery::class) {
            return new GenericObjectType(ActiveQuery::class, [$modelType]);
        }

        if ($returnType::class === GenericObjectType::class && $returnType->getClassName() === ActiveQuery::class) {
            return new GenericObjectType(ActiveQuery::class, [$modelType]);
        }

        if (count($classNames) > 0 && $this->isActiveQueryClass($classNames[0])) {
            return new GenericObjectType($classNames[0], [$modelType]);
        }

        return $returnType;
    }

    /**
     * Determines whether the specified class is {@see ActiveRecord} or a subclass.
     *
     * Checks if the given class name corresponds to the {@see ActiveRecord} base class or any of its subclasses by
     * leveraging the reflection provider.
     *
     * This is used to ensure type compatibility and accurate type inference for static {@see ActiveRecord} methods
     * during PHPStan analysis.
     *
     * This method is essential for supporting dynamic return type inference in scenarios where union types or generic
     * {@see ActiveRecord} subclasses are involved, enabling precise type checks and autocompletion.
     *
     * @param string $className Fully qualified class name to check.
     *
     * @return bool `true` if the class is {@see ActiveRecord} or a subclass; `false` otherwise.
     */
    private function isActiveRecordClass(string $className): bool
    {
        if ($this->reflectionProvider->hasClass($className) === false) {
            return false;
        }

        return $this->reflectionProvider->getClass($className)->isSubclassOfClass(
            $this->reflectionProvider->getClass(ActiveRecord::class),
        );
    }

    /**
     * Determines whether the specified class is {@see ActiveQuery} or a subclass.
     *
     * Checks if the given class name corresponds to the {@see ActiveQuery} base class or any of its subclasses by
     * leveraging the reflection provider.
     *
     * This is used to ensure type compatibility and accurate type inference for static {@see ActiveRecord} methods that
     * return query objects during PHPStan analysis.
     *
     * This method is essential for supporting dynamic return type inference in scenarios where union types or generic
     * {@see ActiveQuery} subclasses are involved, enabling precise type checks and autocompletion for query methods.
     *
     * @param string $className Fully qualified class name to check.
     *
     * @return bool `true` if the class is {@see ActiveQuery} or a subclass; `false` otherwise.
     */
    private function isActiveQueryClass(string $className): bool
    {
        if ($this->reflectionProvider->hasClass($className) === false) {
            return false;
        }

        return $this->reflectionProvider->getClass($className)->isSubclassOfClass(
            $this->reflectionProvider->getClass(ActiveQuery::class),
        );
    }
}
