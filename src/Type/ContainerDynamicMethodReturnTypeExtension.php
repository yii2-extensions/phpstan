<?php

declare(strict_types=1);

namespace Yii2\Extensions\PHPStan\Type;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use Yii2\Extensions\PHPStan\ServiceMap;
use yii\di\Container;

final class ContainerDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function __construct(private readonly ServiceMap $serviceMap) {}

    public function getClass(): string
    {
        return Container::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'get';
    }

    /**
     * @throws ShouldNotHappenException
     */
    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
    {
        if (isset($methodCall->args[0]) && $methodCall->args[0] instanceof Arg) {
            $serviceClass = $this->serviceMap->getServiceClassFromNode($methodCall->args[0]->value);
            if ($serviceClass !== null) {
                return new ObjectType($serviceClass);
            }
        }

        return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
    }
}
