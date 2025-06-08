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
    ThisType,
    Type,
    TypeCombinator,
    UnionType,
};
use yii\db\{ActiveQuery, ActiveRecord};

use function count;

/**
 * Provides dynamic return type extension for Yii {@see ActiveRecord} static methods in PHPStan analysis.
 *
 * Integrates Yii's {@see ActiveRecord} static method return types with PHPStan's static analysis, enabling accurate
 * type inference for static methods such as {@see ActiveRecord::findOne()}, {@see ActiveRecord::findAll()}, and custom
 * query methods based on runtime context and method signatures.
 *
 * This extension allows PHPStan to infer the correct return type for {@see ActiveRecord} static methods, supporting
 * both {@see ActiveRecord} object and ActiveQuery result types, and handling the dynamic behavior of static query
 * result types in Yii ORM.
 *
 * The implementation inspects the method's return type and class context to determine the appropriate return type,
 * ensuring that static analysis and IDE autocompletion reflect the actual runtime behavior of {@see ActiveRecord}
 * static methods.
 *
 * Key features.
 * - Dynamic return type inference for static {@see ActiveRecord} query methods.
 * - Ensures compatibility with PHPStan's strict analysis and autocompletion.
 * - Handles runtime context and method signature inspection.
 * - Provides accurate type information for IDEs and static analysis tools.
 * - Supports both {@see ActiveRecord} object and ActiveQuery result types.
 *
 * @see ActiveQueryObjectType for custom query object type handling.
 * @see ActiveRecordObjectType for custom {@see ActiveRecord} object type handling.
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
     * This method is essential for registering the extension with PHPStan's type system, ensuring that static method
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

        if ($returnType instanceof ThisType) {
            return true;
        }

        if ($returnType instanceof UnionType) {
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

        return false;
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

        if ($className instanceof Name === false) {
            return $returnType;
        }

        $name = $scope->resolveName($className);

        if ($returnType instanceof ThisType) {
            return new ActiveRecordObjectType($name);
        }

        if ($returnType instanceof UnionType) {
            return TypeCombinator::union(new NullType(), new ActiveRecordObjectType($name));
        }

        $classNames = $returnType->getObjectClassNames();

        if (count($classNames) > 0 && $classNames[0] === ActiveQuery::class) {
            return new ActiveQueryObjectType($name, false);
        }

        return $returnType;
    }
}
