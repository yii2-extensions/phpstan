<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\type;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\StringType;
use PHPStan\Type\ThisType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use yii\db\ActiveQuery;

use function get_class;
use function in_array;
use function sprintf;

final class ActiveQueryDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return ActiveQuery::class;
    }

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

    /**
     * @throws ShouldNotHappenException
     */
    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type {
        $calledOnType = $scope->getType($methodCall->var);
        if (!$calledOnType instanceof ActiveQueryObjectType) {
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

        if (!in_array($methodName, ['one', 'all'], true)) {
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
}
