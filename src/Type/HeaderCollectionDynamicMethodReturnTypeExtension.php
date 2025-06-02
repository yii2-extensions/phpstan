<?php

declare(strict_types=1);

namespace Yii2\Extensions\PHPStan\Type;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ArrayType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use yii\web\HeaderCollection;

use function count;

final class HeaderCollectionDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return HeaderCollection::class;
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
        if (count($methodCall->args) < 3) {
            // $first === true (the default) and the get-method returns something of type string
            return new StringType();
        }

        /** @var Arg $arg */
        $arg = $methodCall->args[2];
        if ($arg->value instanceof ConstFetch) {
            $value = $arg->value->name->getParts()[0];
            if ($value === 'true') {
                // $first === true, therefore string
                return new StringType();
            }

            if ($value === 'false') {
                // $first === false, therefore string[]
                return new ArrayType(new IntegerType(), new StringType());
            }
        }

        // Unable to figure out the value of third parameter $first, therefore, it can be of either type
        return new UnionType([new ArrayType(new IntegerType(), new StringType()), new StringType()]);
    }
}
