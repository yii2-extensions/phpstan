<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\type;

use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
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
            if ($this->reflectionProvider->hasClass($className)) {
                $classReflection = $this->reflectionProvider->getClass($className);

                return $classReflection->isSubclassOfClass($this->reflectionProvider->getClass(ActiveQuery::class));
            }
        }

        return false;
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
