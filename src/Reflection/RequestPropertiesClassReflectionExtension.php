<?php

declare(strict_types=1);

namespace Yii2\Extensions\PHPStan\Reflection;

use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MissingPropertyFromReflectionException;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\ReflectionProvider;
use yii\console\Request as ConsoleRequest;
use yii\web\Request as WebRequest;

final class RequestPropertiesClassReflectionExtension implements PropertiesClassReflectionExtension
{
    public function __construct(private readonly ReflectionProvider $reflectionProvider) {}

    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        if ($classReflection->getName() !== ConsoleRequest::class) {
            return false;
        }

        return $this->reflectionProvider->getClass(WebRequest::class)->hasProperty($propertyName);
    }

    /**
     * @throws MissingPropertyFromReflectionException
     */
    public function getProperty(ClassReflection $classReflection, string $propertyName): PropertyReflection
    {
        return $this->reflectionProvider
            ->getClass(WebRequest::class)
            ->getProperty($propertyName, new OutOfClassScope());
    }
}
