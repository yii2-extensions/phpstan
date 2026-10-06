<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\type;

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
     *
     * An input array that is known to be empty is returned unchanged. A call with an unpacked argument keeps the return
     * type declared by Yii.
     */
    public function getTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        Scope $scope,
    ): Type {
        $args = $methodCall->getArgs();

        $fallbackType = ParametersAcceptorSelector::selectFromArgs(
            $scope,
            $args,
            $methodReflection->getVariants(),
        )->getReturnType();

        foreach ($args as $arg) {
            if ($arg->unpack) {
                return $fallbackType;
            }
        }

        if (isset($args[0]) === false) {
            return $fallbackType;
        }

        $messagesType = $scope->getType($args[0]->value);

        if ($messagesType->isArray()->yes() === false) {
            return $fallbackType;
        }

        if ($messagesType->isIterableAtLeastOnce()->no()) {
            return $messagesType;
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
