<?php

declare(strict_types=1);

namespace Yii2\Extensions\PHPStan\Type;

use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\NullType;
use PHPStan\Type\ThisType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

final class ActiveRecordDynamicStaticMethodReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{
    private ReflectionProvider $reflectionProvider;

    public function __construct(
        ReflectionProvider $reflectionProvider,
    ) {
        $this->reflectionProvider = $reflectionProvider;
    }

    public function getClass(): string
    {
        return ActiveRecord::class;
    }

    /**
     * @throws ShouldNotHappenException
     */
    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        /** @phpstan-ignore staticMethod.deprecated */
        $returnType = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
        if ($returnType instanceof ThisType) {
            return true;
        }

        if ($returnType instanceof UnionType) {
            foreach ($returnType->getTypes() as $type) {
                if ($type->isObject()->yes()) {
                    return $this->reflectionProvider->hasClass($this->getClass()) &&
                        $type->getClassName() === $this->getClass();
                }
            }
        }

        return $returnType->isObject()->yes() && $returnType->getClassName() === ActiveQuery::class;
    }

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
                new ActiveRecordObjectType($name),
            );
        }

        return new ActiveQueryObjectType($name, false);
    }
}
