<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\type;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\{ConstFetch, MethodCall};
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\{
    ArrayType,
    DynamicMethodReturnTypeExtension,
    IntegerType,
    StringType,
    Type,
    UnionType,
};
use yii\web\HeaderCollection;

use function count;

/**
 * Provides dynamic return type extension for Yii {@see HeaderCollection::get()} method in PHPStan analysis.
 *
 * Integrates Yii's {@see HeaderCollection} dynamic return types with PHPStan static analysis, enabling accurate type
 * inference for the {@see HeaderCollection::get()} method based on the runtime context and method arguments.
 *
 * This extension allows PHPStan to infer the correct return type for the {@see HeaderCollection::get()}` method,
 * supporting both string and array return types depending on the third argument, and handling the dynamic behavior of
 * header value retrieval in Yii HTTP handling.
 *
 * The implementation inspects the method arguments to determine the appropriate return type, ensuring that static
 * analysis and IDE autocompletion reflect the actual runtime behavior of {@see HeaderCollection::get()} method.
 *
 * Key features.
 * - Dynamic return type inference for the {@see HeaderCollection::get()} method based on the third argument.
 * - Ensures compatibility with PHPStan strict analysis and autocompletion.
 * - Handles runtime context and method argument inspection.
 * - Provides accurate type information for IDEs and static analysis tools.
 * - Supports both string and array result types for header retrieval.
 *
 * @see DynamicMethodReturnTypeExtension for PHPStan dynamic return type extension contract.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class HeaderCollectionDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    /**
     * Returns the class name for which this dynamic return type extension applies.
     *
     * Specifies the fully qualified class name of the Yii {@see HeaderCollection} class that this extension targets
     * for dynamic return type inference in PHPStan analysis.
     *
     * This enables PHPStan to apply custom return type logic for the {@see HeaderCollection::get()} method, supporting
     * accurate type inference and IDE autocompletion for header value retrieval in Yii HTTP handling.
     *
     * @return string Fully qualified class name of the supported header collection class.
     *
     * @phpstan-return class-string
     */
    public function getClass(): string
    {
        return HeaderCollection::class;
    }

    /**
     * @throws ShouldNotHappenException if the method is not supported or the arguments are invalid.
     */
    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type {
        $args = $methodCall->getArgs();

        if (count($args) < 3) {
            return new StringType();
        }

        /** @var Arg $arg */
        $arg = $args[2] ?? null;

        if ($arg->value instanceof ConstFetch) {
            $value = $arg->value->name->getParts()[0];
            if ($value === 'true') {
                return new StringType();
            }

            if ($value === 'false') {
                return new ArrayType(new IntegerType(), new StringType());
            }
        }

        return new UnionType([new ArrayType(new IntegerType(), new StringType()), new StringType()]);
    }

    /**
     * Determines whether the given method is supported for dynamic return type inference.
     *
     * Checks if the method is {@see HeaderCollection::get()}, which is the only method supported by this extension for
     * dynamic return type analysis.
     *
     * This ensures that PHPStan applies the custom return type logic exclusively to the {@see HeaderCollection::get()}
     * method, maintaining strict compatibility and accurate type inference for header value retrieval in Yii HTTP
     * handling.
     *
     * @param MethodReflection $methodReflection Reflection instance for the method.
     *
     * @return bool `true` if the method is {@see HeaderCollection::get()}; `false` otherwise.
     */
    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'get';
    }
}
