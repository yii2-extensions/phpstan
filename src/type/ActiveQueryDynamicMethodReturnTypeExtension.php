<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\type;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\{
    ArrayType,
    IntegerType,
    MixedType,
    NullType,
    StringType,
    ThisType,
    Type,
    TypeCombinator,
};
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use yii\db\ActiveQuery;

use function count;
use function get_class;
use function in_array;
use function sprintf;

/**
 * Provides dynamic return type extension for Yii ActiveQuery methods in PHPStan analysis.
 *
 * Integrates Yii's ActiveQuery dynamic return types with PHPStan's static analysis, enabling accurate type inference
 * for methods such as {@see ActiveQuery::asArray()}, {@see ActiveQuery::one()}, and {@see ActiveQuery::all()} based on
 * runtime context and method arguments.
 *
 * This extension allows PHPStan to infer the correct return type for ActiveQuery methods, supporting both array and
 * ActiveRecord object results, and handling the dynamic behavior of query result types in Yii ORM.
 *
 * The implementation inspects the method name and arguments to determine the appropriate return type, ensuring that
 * static analysis and IDE autocompletion reflect the actual runtime behavior of ActiveQuery methods.
 *
 * Key features.
 * - Dynamic return type inference for {@see ActiveQuery::asArray()}, {@see ActiveQuery::one()}, and
 *   {@see ActiveQuery::all()} methods.
 * - Ensures compatibility with PHPStan's strict analysis and autocompletion.
 * - Handles runtime context and method argument inspection.
 * - Provides accurate type information for IDEs and static analysis tools.
 * - Supports both array and ActiveRecord object result types.
 *
 * @see ActiveQueryObjectType for custom query object type handling.
 * @see ActiveRecordObjectType for custom ActiveRecord object type handling.
 * @see DynamicMethodReturnTypeExtension for PHPStan dynamic return type extension contract.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class ActiveQueryDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    /**
     * Returns the class name for which this dynamic return type extension applies.
     *
     * @return string Fully qualified class name of the supported class.
     *
     * @phpstan-return class-string
     */
    public function getClass(): string
    {
        return ActiveQuery::class;
    }

    /**
     * Infers the return type for a method call on an ActiveQuery instance based on method name and arguments.
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

        if ($calledOnType instanceof ActiveQueryObjectType === false) {
            throw new ShouldNotHappenException(
                sprintf(
                    'Unexpected type %s during method call %s at line %d',
                    get_class($calledOnType),
                    $methodReflection->getName(),
                    $methodCall->getLine(),
                ),
            );
        }

        $methodName = $methodReflection->getName();

        if ($methodName === 'asArray') {
            $argType = isset($methodCall->args[0]) && $methodCall->args[0] instanceof Arg
                ? $scope->getType($methodCall->args[0]->value) : new ConstantBooleanType(true);

            if ($argType->isTrue()->yes()) {
                $boolValue = true;
            } elseif ($argType->isFalse()->yes()) {
                $boolValue = false;
            } else {
                throw new ShouldNotHappenException(
                    sprintf('Invalid argument provided to asArray method at line %d', $methodCall->getLine()),
                );
            }

            return new ActiveQueryObjectType($calledOnType->getModelClass(), $boolValue);
        }

        if (in_array($methodName, ['one', 'all'], true) === false) {
            return new ActiveQueryObjectType($calledOnType->getModelClass(), $calledOnType->isAsArray());
        }

        if ($methodName === 'one') {
            return TypeCombinator::union(
                new NullType(),
                $calledOnType->isAsArray()
                    ? new ArrayType(new StringType(), new MixedType())
                    : new ActiveRecordObjectType($calledOnType->getModelClass()),
            );
        }

        return new ArrayType(
            new IntegerType(),
            $calledOnType->isAsArray()
                ? new ArrayType(new StringType(), new MixedType())
                : new ActiveRecordObjectType($calledOnType->getModelClass()),
        );
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
        $variants = $methodReflection->getVariants();

        if (count($variants) > 0) {
            $returnType = $variants[0]->getReturnType();

            if ($returnType instanceof ThisType) {
                return true;
            }
        }

        return in_array($methodReflection->getName(), ['one', 'all'], true);
    }
}
