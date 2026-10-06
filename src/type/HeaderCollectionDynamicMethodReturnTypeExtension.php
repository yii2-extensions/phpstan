<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\type;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\{MethodReflection, ParametersAcceptorSelector};
use PHPStan\Type\{DynamicMethodReturnTypeExtension, Type, TypeCombinator};
use yii\web\HeaderCollection;

/**
 * Removes `null` from the {@see HeaderCollection::get()} return type when the `$default` argument cannot be `null`.
 *
 * Every other call, including one with an unpacked argument, is left to PHPStan, which applies the `$first`
 * conditional return type declared by Yii.
 *
 * {@see DynamicMethodReturnTypeExtension} for PHPStan dynamic return type extension contract.
 */
final class HeaderCollectionDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    /**
     * Returns the Yii header collection class supported by this extension.
     *
     * @phpstan-return class-string
     */
    public function getClass(): string
    {
        return HeaderCollection::class;
    }

    /**
     * Returns the declared return type without `null` when the `$default` argument cannot be `null`.
     *
     * @param MethodReflection $methodReflection Reflection of the called method.
     * @param MethodCall $methodCall Method call with arguments already normalized by PHPStan.
     * @param Scope $scope Current PHPStan analysis scope.
     *
     * @return Type|null Non-nullable header type, or `null` to defer to the return type declared by Yii, also when an
     * argument is unpacked.
     */
    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type|null {
        $args = $methodCall->getArgs();

        foreach ($args as $arg) {
            if ($arg->unpack) {
                return null;
            }
        }

        if (isset($args[1]) === false || $scope->getType($args[1]->value)->isNull()->no() === false) {
            return null;
        }

        return TypeCombinator::removeNull(
            ParametersAcceptorSelector::selectFromArgs(
                $scope,
                $args,
                $methodReflection->getVariants(),
            )->getReturnType(),
        );
    }

    /**
     * Returns whether the reflected method is {@see HeaderCollection::get()}.
     */
    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'get';
    }
}
