<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\type;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\{MethodReflection, ParametersAcceptorSelector};
use PHPStan\Type\{ArrayType, DynamicStaticMethodReturnTypeExtension, Type};
use yii\log\Target;

/**
 * Preserves Yii log-message key and value types through {@see Target::filterMessages()} calls.
 *
 * {@see DynamicStaticMethodReturnTypeExtension} for PHPStan dynamic static return type extension contract.
 */
final class TargetFilterMessagesDynamicStaticMethodReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{
    /**
     * Returns the Yii log target class supported by this extension.
     *
     * @phpstan-return class-string
     */
    public function getClass(): string
    {
        return Target::class;
    }

    /**
     * Returns an array retaining the input message key and tuple types while allowing filtering to produce an empty
     * result or gaps in integer keys.
     */
    public function getTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        Scope $scope,
    ): Type {
        $fallbackType = ParametersAcceptorSelector::selectFromArgs(
            $scope,
            $methodCall->getArgs(),
            $methodReflection->getVariants(),
        )->getReturnType();

        $argument = $methodCall->getRawArgs()[0] ?? null;

        if (!$argument instanceof Arg) {
            return $fallbackType;
        }

        $messagesType = $scope->getType($argument->value);

        if (!$messagesType->isArray()->yes()) {
            return $fallbackType;
        }

        return new ArrayType(
            $messagesType->getIterableKeyType(),
            $messagesType->getIterableValueType(),
        );
    }

    /**
     * Returns whether the reflected method is Yii's message filter.
     */
    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'filterMessages';
    }
}
