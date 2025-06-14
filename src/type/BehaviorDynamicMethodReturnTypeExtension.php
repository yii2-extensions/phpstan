<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\type;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\{OutOfClassScope, Scope};
use PHPStan\Reflection\{ClassReflection, MethodReflection, ReflectionProvider};
use PHPStan\Type\{DynamicMethodReturnTypeExtension, MixedType, Type};
use yii\base\Component;
use yii2\extensions\phpstan\ServiceMap;

use function count;

/**
 * Provides dynamic return type extension for Yii Behavior methods in PHPStan analysis.
 *
 * Integrates Yii Behavior with PHPStan dynamic method return type extension system, enabling detection and resolution
 * of return types for methods provided by attached behaviors on {@see Component} subclasses during static analysis.
 *
 * This extension inspects the behaviors attached to a given class and determines if any of them provide the requested
 * method, allowing PHPStan to infer the correct return type for dynamically available methods as if they were natively
 * declared.
 *
 * Key features.
 * - Delegates to the native method return type if not found in behaviors.
 * - Detects and resolves return types for methods provided by behaviors attached to {@see Component} subclasses.
 * - Ensures compatibility with PHPStan strict static analysis and autocompletion.
 * - Integrates with {@see ServiceMap} for efficient behavior lookup by class name.
 *
 * @see DynamicMethodReturnTypeExtension for PHPStan dynamic return type extension contract.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class BehaviorDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    /**
     * Creates a new instance of the {@see BehaviorDynamicMethodReturnTypeExtension} class.
     *
     * @param ReflectionProvider $reflectionProvider Reflection provider for class and property lookups.
     * @param ServiceMap $serviceMap Service map for resolving component classes by ID.
     */
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
        private readonly ServiceMap $serviceMap,
    ) {}

    /**
     * Returns the class name for which this dynamic method return type extension applies.
     *
     * Specifies the fully qualified class name of the supported class, enabling PHPStan to associate this extension
     * with method calls on the {@see Component} base class and its subclasses.
     *
     * This method is essential for registering the extension with PHPStan type system, ensuring that dynamic method
     * return type inference is applied to the correct class hierarchy during static analysis and IDE autocompletion.
     *
     * @return string Fully qualified class name of the supported {@see Component} class.
     *
     * @phpstan-return class-string
     */
    public function getClass(): string
    {
        return Component::class;
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type {
        $callerType = $scope->getType($methodCall->var);
        $methodName = $methodReflection->getName();

        foreach ($callerType->getObjectClassNames() as $className) {
            if ($this->reflectionProvider->hasClass($className)) {
                $classReflection = $this->reflectionProvider->getClass($className);
                $behaviorMethod = $this->findMethodInBehaviors($classReflection, $methodName);

                if ($behaviorMethod !== null) {
                    return $this->getFirstVariantReturnType($behaviorMethod);
                }
            }
        }

        return $this->getFirstVariantReturnType($methodReflection);
    }

    /**
     * Determines whether the specified method is supported for dynamic return type inference.
     *
     * Checks if the method is not natively declared on its declaring class, indicating that it may be provided
     * dynamically by an attached behavior. This enables PHPStan to apply dynamic return type inference only to methods
     * that aren't part of the native class definition, ensuring accurate analysis for behavior-injected methods on
     * {@see Component} subclasses.
     *
     * @param MethodReflection $methodReflection Reflection instance for the method being analyzed.
     *
     * @return bool `true` if a behavior doesn't natively declare and may provide the method; `false` otherwise.
     */
    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getDeclaringClass()->hasNativeMethod($methodReflection->getName()) === false;
    }

    /**
     * Searches for a method provided by behaviors attached to the specified class.
     *
     * Iterates over all behaviors attached to the given class and checks if any of them declare the requested method.
     *
     * This enables dynamic method resolution for behaviors in PHPStan static analysis, allowing detection of methods
     * that aren't natively declared on the component class but are available via attached behaviors.
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

    /**
     * Retrieves the return type of the first callable variant for the given method reflection.
     *
     * This method is used to support dynamic return type inference for behavior injected methods, ensuring that PHPStan
     * can accurately reflect the return type of methods provided by attached behaviors or, if not available, fall back
     * to a generic mixed type for robust static analysis.
     *
     * @param MethodReflection $methodReflection Reflection instance for the method whose return type is being resolved.
     *
     * @return Type Return type of the first variant if available; otherwise, a {@see MixedType}.
     */
    private function getFirstVariantReturnType(MethodReflection $methodReflection): Type
    {
        $variants = $methodReflection->getVariants();

        if (count($variants) > 0) {
            return $variants[0]->getReturnType();
        }

        return new MixedType();
    }
}
