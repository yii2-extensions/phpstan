<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\type;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\{MethodReflection, ParametersAcceptorSelector, ReflectionProvider};
use PHPStan\Type\{DynamicMethodReturnTypeExtension, MixedType, ObjectType, Type};
use yii\di\ServiceLocator;
use yii2\extensions\phpstan\ServiceMap;

/**
 * Provides dynamic return type extension for Yii Service Locator component resolution in PHPStan analysis.
 *
 * Integrates the Yii Service Locator service {@see ServiceLocator} with PHPStan dynamic method return type extension
 * system, enabling precise type inference for {@see ServiceLocator::get()} calls based on component ID and the
 * {@see ServiceMap}.
 *
 * This extension analyzes the first argument of {@see ServiceLocator::get()} to determine the most accurate return
 * type, returning an {@see ObjectType} for known component classes or a {@see MixedType} for unknown or dynamic ID.
 *
 * Key features:
 * - Accurate return type inference for {@see ServiceLocator::get()} based on component ID string.
 * - Compatible with PHPStan strict static analysis and autocompletion.
 * - Falls back to method signature return type for unsupported or invalid calls.
 * - Supports Yii modules, applications, and any class extending {@see ServiceLocator}.
 * - Uses {@see ServiceMap} to resolve component class names.
 *
 * @see DynamicMethodReturnTypeExtension for PHPStan dynamic return type extension contract.
 * @see ServiceMap for service and component map for Yii Application static analysis.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class ServiceLocatorDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    /**
     * Creates a new instance of the {@see ServiceLocatorDynamicMethodReturnTypeExtension} class.
     *
     * @param ReflectionProvider $reflectionProvider Reflection provider for class and property lookups.
     * @param ServiceMap $serviceMap Service and component map for Yii Application static analysis.
     */
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
        private readonly ServiceMap $serviceMap,
    ) {}

    /**
     * Returns the class name for which this dynamic return type extension applies.
     *
     * Specifies the fully qualified class name of the Yii ServiceLocator {@see ServiceLocator} that this extension
     * target for dynamic return type inference in PHPStan analysis.
     *
     * This method enables PHPStan to associate the extension with the {@see ServiceLocator} class and all its
     * subclasses (like Module and Application), ensuring that dynamic return type logic is applied to component
     * resolution calls.
     *
     * @return string Fully qualified class name of the supported ServiceLocator class.
     *
     * @phpstan-return class-string
     */
    public function getClass(): string
    {
        return ServiceLocator::class;
    }

    /**
     * Infers the return type for a {@see ServiceLocator::get()} method call based on the provided component ID
     * argument.
     *
     * Determines the most accurate return type for component resolution by analyzing the first argument of the
     * {@see ServiceLocator::get()} call.
     *
     * - If the argument is a constant string and matches a known component in the {@see ServiceMap}, returns an
     *   {@see ObjectType} for the resolved class.
     * - If the argument is a class name known to the {@see ReflectionProvider}, returns an {@see ObjectType} for that
     *   class.
     * - Otherwise, returns a {@see MixedType} to indicate an unknown or dynamic component type.
     *
     * Falls back to the default method signature return type for unsupported or invalid calls, ensuring compatibility
     * with PHPStan static analysis and IDE autocompletion.
     *
     * @param MethodReflection $methodReflection Reflection instance for the method being analyzed.
     * @param MethodCall $methodCall AST node for the method call expression.
     * @param Scope $scope PHPStan analysis scope for type resolution.
     *
     * @return Type Inferred return type for the component resolution call.
     */
    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type {
        if (isset($methodCall->args[0]) === false || $methodCall->args[0]::class !== Arg::class) {
            return ParametersAcceptorSelector::selectFromArgs(
                $scope,
                $methodCall->getArgs(),
                $methodReflection->getVariants(),
            )->getReturnType();
        }

        $argType = $scope->getType($methodCall->args[0]->value);
        $constantStrings = $argType->getConstantStrings();

        if (count($constantStrings) === 1) {
            $value = $constantStrings[0]->getValue();

            $componentClass = $this->serviceMap->getComponentClassById($value);

            if ($componentClass !== null) {
                return new ObjectType($componentClass);
            }

            $serviceClass = $this->serviceMap->getServiceById($value);

            if ($serviceClass !== null) {
                return new ObjectType($serviceClass);
            }

            if ($this->reflectionProvider->hasClass($value)) {
                return new ObjectType($value);
            }
        }

        return new MixedType();
    }

    /**
     * Determines whether the specified method is supported for dynamic return type inference.
     *
     * Checks if the method name is {@see ServiceLocator::get}, which is the only method supported by this extension for
     * dynamic return type analysis.
     *
     * This enables PHPStan to apply custom type inference logic exclusively to component resolution calls on the Yii
     * Service Locator {@see ServiceLocator} and its subclasses (Module, Application).
     *
     * @param MethodReflection $methodReflection Reflection instance for the method being analyzed.
     *
     * @return bool `true` if the method is {@see ServiceLocator::get}; `false` otherwise.
     */
    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'get';
    }
}
