<?php

declare(strict_types=1);

namespace Yii2\Extensions\PHPStan\Reflection;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\MissingMethodFromReflectionException;
use PHPStan\Reflection\ReflectionProvider;
use yii\console\Request as ConsoleRequest;
use yii\web\Request as WebRequest;

final class RequestMethodsClassReflectionExtension implements MethodsClassReflectionExtension
{
    public function __construct(private readonly ReflectionProvider $reflectionProvider)
    {
    }

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if ($classReflection->getName() !== ConsoleRequest::class) {
            return false;
        }

        return $this->reflectionProvider->getClass(WebRequest::class)->hasMethod($methodName);
    }

    /**
     * @throws MissingMethodFromReflectionException
     */
    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        return $this->reflectionProvider->getClass(WebRequest::class)->getNativeMethod($methodName);
    }
}
