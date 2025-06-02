<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\reflection;

use PHPStan\Reflection\Annotations\AnnotationsPropertiesClassReflectionExtension;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Dummy\DummyPropertyReflection;
use PHPStan\Reflection\MissingPropertyFromReflectionException;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ObjectType;
use yii\base\Application as BaseApplication;
use yii\web\Application as WebApplication;
use yii2\extensions\phpstan\ServiceMap;

final class ApplicationPropertiesClassReflectionExtension implements PropertiesClassReflectionExtension
{
    public function __construct(
        private AnnotationsPropertiesClassReflectionExtension $annotationsProperties,
        private ReflectionProvider $reflectionProvider,
        private ServiceMap $serviceMap,
    ) {}

    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        $reflectionProviderBaseApplication = $this->reflectionProvider->getClass(BaseApplication::class);

        if (
            $classReflection->getName() !== BaseApplication::class &&
            $classReflection->isSubclassOfClass($reflectionProviderBaseApplication) === false
        ) {
            return false;
        }

        if ($classReflection->getName() !== WebApplication::class) {
            $classReflection = $this->reflectionProvider->getClass(WebApplication::class);
        }

        return $classReflection->hasNativeProperty($propertyName)
            || $this->annotationsProperties->hasProperty($classReflection, $propertyName)
            || $this->serviceMap->getComponentClassById($propertyName);
    }

    /**
     * @throws MissingPropertyFromReflectionException
     */
    public function getProperty(ClassReflection $classReflection, string $propertyName): PropertyReflection
    {
        if ($classReflection->getName() !== WebApplication::class) {
            $classReflection = $this->reflectionProvider->getClass(WebApplication::class);
        }

        if (null !== $componentClass = $this->serviceMap->getComponentClassById($propertyName)) {
            return new ComponentPropertyReflection(
                new DummyPropertyReflection($propertyName),
                new ObjectType($componentClass),
            );
        }

        if ($classReflection->hasNativeProperty($propertyName)) {
            return $classReflection->getNativeProperty($propertyName);
        }

        return $this->annotationsProperties->getProperty($classReflection, $propertyName);
    }
}
