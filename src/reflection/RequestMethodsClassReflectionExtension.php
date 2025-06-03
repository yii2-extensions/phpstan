<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\reflection;

use PHPStan\Reflection\{
    ClassReflection,
    MethodReflection,
    MethodsClassReflectionExtension,
    MissingMethodFromReflectionException,
    ReflectionProvider,
};

/**
 * Provides method reflection for Yii console request classes in PHPStan analysis.
 *
 * Integrates Yii's web request methods into the console request context, enabling accurate type inference and
 * autocompletion for methods that are available on both web and console request classes.
 *
 * This extension allows PHPStan to recognize and reflect methods on the Yii console request instance that are
 * originally defined on the web request class, even if they aren't declared as native methods on the console request.
 *
 * The implementation delegates method lookups to the web request class reflection, ensuring that all valid methods,
 * whether declared natively or inherited—are available for static analysis and IDE support.
 *
 * Key features.
 * - Delegates method reflection to the web request class.
 * - Enables accurate type inference for shared request methods.
 * - Ensures compatibility with PHPStan's strict analysis and autocompletion.
 * - Handles both console and web request contexts.
 * - Supports dynamic method resolution for console requests.
 *
 * @see MethodsClassReflectionExtension for custom methods class reflection extension contract.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class RequestMethodsClassReflectionExtension implements MethodsClassReflectionExtension
{
    /**
     * Creates a new instance of the {@see RequestMethodsClassReflectionExtension} class.
     *
     * @param ReflectionProvider $reflectionProvider Reflection provider for class and property lookups.
     */
    public function __construct(private readonly ReflectionProvider $reflectionProvider) {}

    /**
     * Retrieves the method reflection for a given method on the Yii console request class by delegating to the web
     * request class.
     *
     * Resolves the method reflection for the specified method name by looking it up on the web request class.
     *
     * This ensures that methods available on the web request are also accessible for static analysis and IDE support on
     * the console request.
     *
     * @param ClassReflection $classReflection Class reflection instance for the Yii console request.
     * @param string $methodName Name of the method to retrieve.
     *
     * @throws MissingMethodFromReflectionException if the method doesn't exist on the web request class.
     * @return MethodReflection Method reflection instance for the specified method.
     */
    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        return $this->reflectionProvider->getClass(\yii\web\Request::class)->getNativeMethod($methodName);
    }

    /**
     * Determines whether the specified method exists on the Yii console request class by delegating to the web request
     * class.
     *
     * Checks for the existence of a method on the console request instance by verifying if the corresponding method
     * exists on the web request class.
     *
     * This enables static analysis tools and IDEs to provide accurate autocompletion and type inference for shared
     * request methods.
     *
     * @param ClassReflection $classReflection Class reflection instance for the Yii console request.
     * @param string $methodName Name of the method to check for existence.
     *
     * @return bool `true` if the method exists on the web request class; `false` otherwise.
     */
    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if ($classReflection->getName() !== \yii\console\Request::class) {
            return false;
        }

        return $this->reflectionProvider->getClass(\yii\web\Request::class)->hasMethod($methodName);
    }
}
