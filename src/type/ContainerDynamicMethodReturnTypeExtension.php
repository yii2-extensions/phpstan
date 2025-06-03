<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\type;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\{MethodReflection, ParametersAcceptorSelector};
use PHPStan\Type\{DynamicMethodReturnTypeExtension, ObjectType, Type};
use yii\di\Container;
use yii2\extensions\phpstan\ServiceMap;

/**
 * Provides dynamic return type extension for Yii DI {@see Container::get()} in PHPStan analysis.
 *
 * Integrates Yii's dependency injection container with PHPStan's static analysis, enabling accurate type inference for
 * the {@see Container::get()} method based on the requested service identifier or class name.
 *
 * This extension allows PHPStan to infer the correct return type for service lookups, supporting both string-based and
 * class-based service resolution, and handling dynamic service mapping via the {@see ServiceMap}.
 *
 * The implementation inspects the first argument of the `get()` method call to determine the appropriate return type,
 * ensuring that static analysis and IDE autocompletion reflect the actual runtime behavior of the DI container.
 *
 * Key features.
 * - Dynamic return type inference for {@see Container::get()} based on service ID or class name.
 * - Ensures compatibility with PHPStan's strict analysis and autocompletion.
 * - Handles both string and class-based service lookups.
 * - Integrates with {@see ServiceMap} for accurate service class resolution.
 * - Provides accurate type information for IDEs and static analysis tools.
 *
 * @see DynamicMethodReturnTypeExtension for PHPStan dynamic return type extension contract.
 * @see ServiceMap for component class resolution.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class ContainerDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    /**
     * Creates a new instance of the {@see ContainerDynamicMethodReturnTypeExtension} class.
     *
     * @param ServiceMap $serviceMap Service map for resolving component classes by ID.
     */
    public function __construct(private readonly ServiceMap $serviceMap) {}

    /**
     * Returns the class name for which this dynamic return type extension applies.
     *
     * Specifies the fully qualified class name of the Yii dependency injection container that this extension targets
     * for dynamic return type inference in PHPStan analysis.
     *
     * This method enables PHPStan to associate the extension with the {@see Container} class, ensuring that dynamic
     * return type logic is applied to service resolution calls.
     *
     * @return string Fully qualified class name of the supported container class.
     */
    public function getClass(): string
    {
        return Container::class;
    }

    /**
     * Infers the return type for a method call on a Yii DI Container instance based on the service identifier argument.
     *
     * Determines the correct return type for {@see Container::get()} by inspecting the first argument, which may be a
     * service ID or class name, and resolving the corresponding class using the {@see ServiceMap}.
     *
     * If the service class can be determined, returns an {@see ObjectType} for that class; otherwise, falls back to the
     * default return type as defined by the method signature and arguments.
     *
     * This method enables PHPStan and IDEs to provide accurate type inference and autocompletion for service lookups
     * performed via the DI container, supporting both string and class-based service resolution.
     *
     * @param MethodReflection $methodReflection Reflection instance for the called method.
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
        if (isset($methodCall->args[0]) && $methodCall->args[0] instanceof Arg) {
            $serviceClass = $this->serviceMap->getServiceClassFromNode($methodCall->args[0]->value);

            if ($serviceClass !== null) {
                return new ObjectType($serviceClass);
            }
        }

        return ParametersAcceptorSelector::selectFromArgs(
            $scope,
            $methodCall->getArgs(),
            $methodReflection->getVariants(),
        )->getReturnType();
    }

    /**
     * Determines whether the given method is supported for dynamic return type inference.
     *
     * Checks if the provided {@see MethodReflection} instance corresponds to the {@see Container::get()} method,
     * indicating that this extension should handle dynamic return type inference for the call.
     *
     * This method enables PHPStan to apply the extension logic only to supported methods, ensuring accurate type
     * inference for service resolution calls and avoiding unintended behavior for other methods.
     *
     * @param MethodReflection $methodReflection Reflection instance for the method being analyzed.
     *
     * @return bool `true` if the method is supported for dynamic return type inference; `false` otherwise.
     */
    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'get';
    }
}
