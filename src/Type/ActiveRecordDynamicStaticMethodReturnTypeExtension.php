<?php

declare(strict_types=1);

namespace Yii2\Extensions\PHPStan\Type;

use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ThisType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

use function is_a;

final class ActiveRecordDynamicStaticMethodReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return ActiveRecord::class;
    }

    /**
     * @throws ShouldNotHappenException
     */
    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        $returnType = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
        if ($returnType instanceof ThisType) {
            return true;
        }

        if ($returnType instanceof UnionType) {
            foreach ($returnType->getTypes() as $type) {
                if ($type instanceof ObjectType) {
                    return is_a($type->getClassName(), $this->getClass(), true);
                }
            }
        }

        return $returnType instanceof ObjectType &&
            is_a($returnType->getClassName(), ActiveQuery::class, true);
    }

    /**
     * @throws ShouldNotHappenException
     */
    public function getTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        Scope $scope
    ): Type {
        $className = $methodCall->class;
        $returnType = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();

        if (!$className instanceof Name) {
            return $returnType;
        }

        $name = $scope->resolveName($className);

        if ($returnType instanceof ThisType) {
            return new ActiveRecordObjectType($name);
        }

        if ($returnType instanceof UnionType) {
            return TypeCombinator::union(
                new NullType(),
                new ActiveRecordObjectType($name)
            );
        }

        return new ActiveQueryObjectType($name, false);
    }
}
