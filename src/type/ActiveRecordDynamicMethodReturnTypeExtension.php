<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\type;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\{DynamicMethodReturnTypeExtension, Type};
use yii\db\ActiveRecord;

use function count;
use function in_array;
use function sprintf;

/**
 * Provides dynamic return type extension for Yii {@see ActiveRecord} relation methods in PHPStan analysis.
 *
 * Integrates Yii's {@see ActiveRecord} relation methods with PHPStan's static analysis, enabling accurate type
 * inference for methods such as {@see ActiveRecord::hasOne()} and {@see ActiveRecord::hasMany()} based on the provided
 * model class argument.
 *
 * This extension allows PHPStan to infer the correct return type for {@see ActiveRecord} relation methods, supporting
 * dynamic relation definitions and ensuring that static analysis and IDE autocompletion reflect the actual runtime
 * behavior of relation methods in Yii ORM.
 *
 * The implementation inspects the method arguments to determine the related model class, returning an
 * {@see ActiveQueryObjectType} for the specified relation.
 *
 * This enables precise type information for relation queries and supports strict analysis of relation usage in Yii
 * applications.
 *
 * Key features.
 * - Dynamic return type inference for `hasOne()` and `hasMany()` relation methods.
 * - Ensures compatibility with PHPStan's strict analysis and autocompletion.
 * - Handles runtime context and method argument inspection for relation definitions.
 * - Provides accurate type information for IDEs and static analysis tools.
 * - Supports both single and multiple record relation types.
 *
 * @see ActiveQueryObjectType for custom query object type handling.
 * @see DynamicMethodReturnTypeExtension for PHPStan dynamic return type extension contract.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class ActiveRecordDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    /**
     * Returns the class name for which this dynamic return type extension applies.
     *
     * Specifies the fully qualified class name of the Yii {@see ActiveRecord} base class that this extension targets
     * for dynamic return type inference in PHPStan analysis.
     *
     * This enables PHPStan to apply custom return type logic for {@see ActiveRecord} relation methods such as
     * {@see ActiveRecord}::hasOne()} and {@see ActiveRecord::hasMany()}, supporting accurate type inference and IDE
     * autocompletion for dynamic relation definitions.
     *
     * @return string Fully qualified class name of the supported {@see ActiveRecord} class.
     *
     * @phpstan-return class-string<\yii\db\ActiveRecord>
     */
    public function getClass(): string
    {
        return ActiveRecord::class;
    }

    /**
     * Infers the return type for a relation method call on a Yii {@see ActiveRecord} instance based on the provided
     * model class argument.
     *
     * Determines the correct return type for {@see ActiveRecord::hasOne()} and {@see ActiveRecord::hasMany()} relation
     * methods by inspecting the first argument which should be a constant string representing the related model class.
     *
     * Returns an {@see ActiveQueryObjectType} for the specified relation, enabling accurate type inference for static
     * analysis and IDE support.
     *
     * @param MethodReflection $methodReflection Reflection instance for the called relation method.
     * @param MethodCall $methodCall AST node for the method call expression.
     * @param Scope $scope PHPStan analysis scope for type resolution.
     *
     * @throws ShouldNotHappenException if the method argument is missing, not a constant string, or invalid for
     * relation definition.
     *
     * @return Type Inferred return type for the relation method call as an {@see ActiveQueryObjectType}.
     */
    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type {
        $arg = $methodCall->args[0] ?? null;

        if ($arg === null || $arg instanceof Arg === false) {
            throw new ShouldNotHappenException(
                sprintf(
                    'Invalid or missing argument for method %s at line %d',
                    $methodReflection->getName(),
                    $methodCall->getLine(),
                ),
            );
        }

        $argType = $scope->getType($arg->value);
        $constantStrings = $argType->getConstantStrings();

        if (count($constantStrings) === 0) {
            throw new ShouldNotHappenException(
                sprintf(
                    'Invalid argument provided to method %s' . PHP_EOL .
                    'Hint: You should use ::class instead of ::className()',
                    $methodReflection->getName(),
                ),
            );
        }

        return new ActiveQueryObjectType($constantStrings[0]->getValue(), false);
    }

    /**
     * Determines whether the given method is supported for dynamic return type inference.
     *
     * Checks if the method name is one of the supported relation methods {@see ActiveRecord::hasOne},
     * {@see ActiveRecord::hasMany} for which this extension provides dynamic return type inference.
     *
     * Only these methods are eligible for custom type resolution.
     *
     * @param MethodReflection $methodReflection Reflection instance for the method being analyzed.
     *
     * @return bool `true` if the method is supported for dynamic return type inference; `false` otherwise.
     */
    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return in_array($methodReflection->getName(), ['hasOne', 'hasMany'], true);
    }
}
