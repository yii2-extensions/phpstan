<?php

declare(strict_types=1);

namespace Yii2\Extensions\PHPStan\Reflection;

use PHPStan\Reflection\Annotations\AnnotationsPropertiesClassReflectionExtension;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Dummy\DummyPropertyReflection;
use PHPStan\Reflection\MissingPropertyFromReflectionException;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Type\MixedType;
use yii\web\User;

final class UserPropertiesClassReflectionExtension implements PropertiesClassReflectionExtension
{
    public function __construct(private readonly AnnotationsPropertiesClassReflectionExtension $annotationsProperties) {}

    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        if ($classReflection->getName() !== User::class) {
            return false;
        }

        return $classReflection->hasNativeProperty($propertyName)
            || $this->annotationsProperties->hasProperty($classReflection, $propertyName);
    }

    /**
     * @throws MissingPropertyFromReflectionException
     */
    public function getProperty(ClassReflection $classReflection, string $propertyName): PropertyReflection
    {
        if ($propertyName === 'identity') {
            return new ComponentPropertyReflection(new DummyPropertyReflection(), new MixedType());
        }

        if ($classReflection->hasNativeProperty($propertyName)) {
            return $classReflection->getNativeProperty($propertyName);
        }

        return $this->annotationsProperties->getProperty($classReflection, $propertyName);
    }
}
