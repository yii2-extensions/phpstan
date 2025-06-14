<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\method;

use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\{
    ClassReflection,
    MethodReflection,
    MethodsClassReflectionExtension,
    ReflectionProvider,
};
use yii\base\Component;
use yii2\extensions\phpstan\ServiceMap;

/**
 * Provides method reflection for Yii Behavior in PHPStan analysis.
 *
 * Integrates Yii Behavior with PHPStan method reflection extension, enabling detection and resolution of methods
 * provided by attached behaviors on {@see Component} subclasses during static analysis.
 *
 * This extension inspects the behaviors attached to a given class and determines if any of them provide the requested
 * method, allowing PHPStan to recognize available methods as if they were natively declared.
 *
 * Key features.
 * - Delegates to the native {@see Component} method if not found in behaviors.
 * - Detects methods provided by behaviors attached to {@see Component} subclasses.
 * - Ensures compatibility with PHPStan strict static analysis and autocompletion.
 * - Integrates with {@see ServiceMap} for efficient behavior lookup by class name.
 *
 * @see Component for Yii Component class.
 * @see MethodsClassReflectionExtension for custom methods class reflection extension contract.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class BehaviorMethodsClassReflectionExtension implements MethodsClassReflectionExtension
{
    /**
     * Creates a new instance of the {@see BehaviorMethodsClassReflectionExtension} class.
     *
     * @param ReflectionProvider $reflectionProvider Reflection provider for class and property lookups.
     * @param ServiceMap $serviceMap Service map for resolving component classes by ID.
     */
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
        private readonly ServiceMap $serviceMap,
    ) {}

    /**
     * Retrieves the method reflection for a given method name, including those provided by attached behaviors.
     *
     * Resolves the {@see MethodReflection} for the specified method name on the given class, searching first among
     * methods provided by behaviors attached to the class. If the method is not found in any behavior, it delegates
     * to the native {@see Component} method resolution.
     *
     * This enables PHPStan to recognize available methods from behaviors as if they were natively declared on the
     * component class, supporting accurate static analysis and autocompletion.
     *
     * @param ClassReflection $classReflection Reflection of the class being analyzed.
     * @param string $methodName Name of the method to resolve.
     *
     * @return MethodReflection Reflection instance for the resolved method.
     */
    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        $behaviorMethod = $this->findMethodInBehaviors($classReflection, $methodName);

        assert($behaviorMethod !== null);

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
        $behaviors = $this->serviceMap->getBehaviorsByClassName($classReflection->getName());

        foreach ($behaviors as $behaviorClass) {
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
