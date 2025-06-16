<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\type;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\{MethodReflection, ParametersAcceptorSelector, ReflectionProvider};
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\{DynamicMethodReturnTypeExtension, MixedType, ObjectType, Type};
use yii\di\Container;
use yii2\extensions\phpstan\ServiceMap;

/**
 * Provides dynamic return type extension for Yii DI container service resolution in PHPStan analysis.
 *
 * Integrates the Yii DI container service {@see Container} with PHPStan dynamic method return type extension system,
 * enabling precise type inference for {@see Container::get()} calls based on service ID and the {@see ServiceMap}.
 *
 * This extension analyzes the first argument of {@see Container::get()} to determine the most accurate return type,
 * returning an {@see ObjectType} for known service classes or a {@see MixedType} for unknown or dynamic IDs.
 *
 * Key features.
 * - Accurate return type inference for {@see Container::get()} based on service ID string.
 * - Compatible with PHPStan strict static analysis and autocompletion.
 * - Falls back to method signature return type for unsupported or invalid calls.
 * - Uses {@see ServiceMap} to resolve component class names.
 *
 * @see DynamicMethodReturnTypeExtension for PHPStan dynamic return type extension contract.
 * @see ServiceMap for application class resolution.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class ContainerDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    /**
     * Creates a new instance of the {@see ContainerDynamicMethodReturnTypeExtension} class.
     *
     * @param ReflectionProvider $reflectionProvider Reflection provider for class and property lookups.
     * @param ServiceMap $serviceMap Service map for resolving component classes by ID.
     */
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
        private readonly ServiceMap $serviceMap,
    ) {}

    /**
     * Returns the class name for which this dynamic return type extension applies.
     *
     * Specifies the fully qualified class name of the Yii DI container service {@see Container} that this extension
     * targets for dynamic return type inference in PHPStan analysis.
     *
     * This method enables PHPStan to associate the extension with the {@see Container} class, ensuring that dynamic
     * return type logic is applied to service resolution calls.
     *
     * @return string Fully qualified class name of the supported container class.
     *
     * @phpstan-return class-string
     */
    public function getClass(): string
    {
        return Container::class;
    }

    /**
     * Infers the return type for a {@see Container::get()} method call based on the provided service ID argument.
     *
     * Determines the most accurate return type for service resolution by analyzing the first argument of the
     * {@see Container::get()} call.
     *
     * - If the argument is a constant string and matches a known service in the {@see ServiceMap}, returns an
     *   {@see ObjectType} for the resolved class.
     * - If the argument is a class name known to the {@see ReflectionProvider}, returns an {@see ObjectType} for that
     *   class.
     * - Otherwise, returns a {@see MixedType} to indicate an unknown or dynamic service type.
     *
     * Falls back to the default method signature return type for unsupported or invalid calls, ensuring compatibility
     * with PHPStan static analysis and IDE autocompletion.
     *
     * @param MethodReflection $methodReflection Reflection for the called method.
     * @param MethodCall $methodCall AST node for the method call expression.
     * @param Scope $scope PHPStan analysis scope for type resolution.
     *
     * @return Type Inferred return type for the service resolution call.
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

        if ($argType::class === ConstantStringType::class) {
            $constantString = $argType->getConstantStrings()[0] ?? null;
            $value = $constantString?->getValue() ?? '';
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
     * Checks if the method name is {@see Container::get}, which is the only method supported by this extension for
     * dynamic return type analysis.
     *
     * This enables PHPStan to apply custom type inference logic exclusively to service resolution calls on the Yii DI
     * container service {@see Container}.
     *
     * @param MethodReflection $methodReflection Reflection instance for the method being analyzed.
     *
     * @return bool `true` if the method is {@see Container::get}; `false` otherwise.
     */
    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'get';
    }
}
