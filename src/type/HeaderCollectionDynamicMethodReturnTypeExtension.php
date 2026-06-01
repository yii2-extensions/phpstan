<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\type;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\{ArrayType, DynamicMethodReturnTypeExtension, IntegerType, NullType, StringType, Type, UnionType};
use yii\web\HeaderCollection;

use function count;

/**
 * Infers return types for {@see HeaderCollection::get()} calls in PHPStan analysis.
 *
 * Inspects the method arguments to return `string`, `array<int, string>`, or a union, adding `null` when the default
 * value can be `null` or is omitted.
 *
 * {@see DynamicMethodReturnTypeExtension} for PHPStan dynamic return type extension contract.
 */
final class HeaderCollectionDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    /**
     * Returns the class name for which this dynamic return type extension applies.
     *
     * Specifies the fully qualified class name of the Yii {@see HeaderCollection} class that this extension targets for
     * dynamic return type inference in PHPStan analysis.
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
     * Infers the dynamic return type for {@see HeaderCollection::get()} based on method arguments.
     *
     * Determines the return type of the {@see HeaderCollection::get()} method by analyzing the provided arguments at
     * call site.
     *
     * The return type is inferred as `string`, `array<int, string>`, or `null` depending on the value of the third
     * argument ('$first') and the default value argument.
     * - If '$first' is `true`, the return type is `string`;
     * - If `false`, it is `array<int, string>`; if omitted or indeterminate, both types are included in a union.
     * - If the default value can be `null`, `null` is also included in the union type.
     *
     * This enables PHPStan to provide accurate type inference and autocompletion for header value retrieval in Yii HTTP
     * handling, reflecting the actual runtime behavior of {@see HeaderCollection::get()}.
     *
     * @param MethodReflection $methodReflection Reflection of the called method.
     * @param MethodCall $methodCall AST node representing the method call.
     * @param Scope $scope Current static analysis scope.
     *
     * @throws ShouldNotHappenException if the method is not supported or arguments are invalid.
     *
     * @return Type Inferred return type: `string`, `array<int, string>`, `null`, or a union of these types.
     */
    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type {
        $args = $methodCall->getArgs();
        $types = [];

        $canBeNull = true;

        if (isset($args[1])) {
            $defaultType = $scope->getType($args[1]->value);
            $canBeNull = $defaultType->accepts(new NullType(), true)->yes();
        }

        if (isset($args[2]) === false) {
            $types[] = new StringType();
        } else {
            $firstType = $scope->getType($args[2]->value);
            $types = match (true) {
                $firstType->isTrue()->yes() => [new StringType()],
                $firstType->isFalse()->yes() => [new ArrayType(new IntegerType(), new StringType())],
                default => [new StringType(), new ArrayType(new IntegerType(), new StringType())],
            };
        }

        if ($canBeNull) {
            $types[] = new NullType();
        }

        return count($types) === 1 ? $types[0] : new UnionType($types);
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
     * @param MethodReflection $methodReflection Reflection instance for the method being analyzed.
     *
     * @return bool `true` if the method is {@see HeaderCollection::get()}; `false` otherwise.
     */
    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'get';
    }
}
