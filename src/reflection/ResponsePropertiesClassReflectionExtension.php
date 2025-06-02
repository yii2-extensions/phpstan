<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\reflection;

use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MissingPropertyFromReflectionException;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\ReflectionProvider;
use yii\console\Response as ConsoleResponse;
use yii\web\Response as WebResponse;

final class ResponsePropertiesClassReflectionExtension implements PropertiesClassReflectionExtension
{
    public function __construct(private readonly ReflectionProvider $reflectionProvider) {}

    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        if ($classReflection->getName() !== ConsoleResponse::class) {
            return false;
        }

        return $this->reflectionProvider->getClass(WebResponse::class)->hasProperty($propertyName);
    }

    /**
     * @throws MissingPropertyFromReflectionException
     */
    public function getProperty(ClassReflection $classReflection, string $propertyName): PropertyReflection
    {
        return $this->reflectionProvider
            ->getClass(WebResponse::class)
            ->getProperty($propertyName, new OutOfClassScope());
    }
}
