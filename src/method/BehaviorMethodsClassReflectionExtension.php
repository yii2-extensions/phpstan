<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\method;

use PHPStan\Analyser\{DeclarationDependencyTracker, OutOfClassScope};
use PHPStan\Reflection\{ClassReflection, MethodReflection, MethodsClassReflectionExtension, ReflectionProvider};
use PHPStan\ShouldNotHappenException;
use yii\base\Component;
use yii2\extensions\phpstan\{ServiceMap, ServiceMapResultCacheValueExtension};

use function sprintf;

/**
 * Resolves methods provided by behaviors attached to {@see Component} subclasses for PHPStan analysis.
 *
 * Inspects the behaviors attached to a given class via the {@see ServiceMap} and determines whether any provide the
 * requested method, allowing PHPStan to recognize behavior methods as if they were natively declared.
 *
 * {@see Component} for Yii Component class.
 * {@see MethodsClassReflectionExtension} for custom methods class reflection extension contract.
 */
final class BehaviorMethodsClassReflectionExtension implements MethodsClassReflectionExtension
{
    /**
     * Creates a new instance of the {@see BehaviorMethodsClassReflectionExtension} class.
     *
     * @param ReflectionProvider $reflectionProvider Reflection provider for class and property lookups.
     * @param ServiceMap $serviceMap Service and component map for Yii Application static analysis.
     * @param DeclarationDependencyTracker $dependencyTracker Records the configuration values each class declaration
     * depends on.
     */
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
        private readonly ServiceMap $serviceMap,
        private readonly DeclarationDependencyTracker $dependencyTracker,
    ) {}

    /**
     * Retrieves the reflection of a method provided by a behavior attached to the given class.
     *
     * PHPStan calls this method only after {@see hasMethod()} returned `true` for the same class and method name.
     *
     * @param ClassReflection $classReflection Reflection of the class being analyzed.
     * @param string $methodName Name of the method to resolve.
     *
     * @throws ShouldNotHappenException if no attached behavior provides the method.
     *
     * @return MethodReflection Reflection instance for the resolved method.
     */
    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        $behaviorMethod = $this->findMethodInBehaviors($classReflection, $methodName);

        if ($behaviorMethod === null) {
            throw new ShouldNotHappenException(
                sprintf(
                    'Method %s::%s() is not provided by any behavior attached to the class.',
                    $classReflection->getName(),
                    $methodName,
                ),
            );
        }

        return $behaviorMethod;
    }

    /**
     * Determines whether the specified method exists on the given class, including methods provided by attached
     * behaviors.
     *
     * Checks if the class is a subclass of {@see Component} and doesn't already declare the method natively. If so,
     * inspect all behaviors attached to the class to determine if any provide the requested method.
     *
     * This enables PHPStan to recognize available methods from behaviors as if they were natively declared on the
     * component class, supporting accurate static analysis and autocompletion.
     *
     * @param ClassReflection $classReflection Reflection of the class being analyzed.
     * @param string $methodName Name of the method to check for existence.
     *
     * @return bool `true` if the method exists on the class via an attached behavior; `false` otherwise.
     */
    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if ($classReflection->isSubclassOfClass($this->reflectionProvider->getClass(Component::class)) === false) {
            return false;
        }

        if ($classReflection->hasNativeMethod($methodName)) {
            return false;
        }

        return $this->findMethodInBehaviors($classReflection, $methodName) !== null;
    }

    /**
     * Searches for a method provided by behaviors attached to the specified class.
     *
     * Iterates over all behaviors attached to the given class and checks if any of them declare the requested method.
     *
     * This enables method resolution for behaviors in PHPStan static analysis, allowing detection of methods that
     * aren't natively declared on the component class but are available via attached behaviors.
     *
     * @param ClassReflection $classReflection Reflection of the class being analyzed.
     * @param string $methodName Name of the method to search for in attached behaviors.
     *
     * @return MethodReflection|null Reflection instance for the resolved method if found in a behavior; {@see null}
     * otherwise.
     */
    private function findMethodInBehaviors(ClassReflection $classReflection, string $methodName): MethodReflection|null
    {
        $this->dependencyTracker->trackValueDependency(
            $classReflection,
            ServiceMapResultCacheValueExtension::class,
            ServiceMapResultCacheValueExtension::behaviorsKey($classReflection->getName()),
        );

        $behaviors = $this->serviceMap->getBehaviorsByClassName($classReflection->getName());

        foreach ($behaviors as $behaviorClass) {
            $this->dependencyTracker->trackClassDependency($classReflection, $behaviorClass);

            if ($this->reflectionProvider->hasClass($behaviorClass)) {
                $behaviorReflection = $this->reflectionProvider->getClass($behaviorClass);

                if ($behaviorReflection->hasMethod($methodName)) {
                    return $behaviorReflection->getMethod($methodName, new OutOfClassScope());
                }
            }
        }

        return null;
    }
}
