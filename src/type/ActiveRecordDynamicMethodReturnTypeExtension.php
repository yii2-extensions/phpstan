<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\type;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\{DynamicMethodReturnTypeExtension, ObjectType, Type};
use PHPStan\Type\Generic\GenericObjectType;
use yii\db\{ActiveQuery, ActiveRecord};

use function count;
use function in_array;
use function sprintf;

/**
 * Infers return types for Yii Active Record relation methods {@see ActiveRecord::hasOne()} and
 * {@see ActiveRecord::hasMany()} in PHPStan analysis.
 *
 * Analyzes the method arguments to return a generic {@see ActiveQuery} type parameterized with the related model class.
 *
 * {@see ActiveQuery} for Active Query API details.
 * {@see ActiveRecord} for Active Record API details.
 * {@see DynamicMethodReturnTypeExtension} for PHPStan dynamic return type extension contract.
 */
final class ActiveRecordDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    /**
     * Returns the class name for which this dynamic return type extension applies.
     *
     * Specifies the fully qualified class name of the {@see ActiveRecord} base class that this extension targets for
     * dynamic return type inference in PHPStan analysis.
     *
     * This enables PHPStan to apply custom return type logic for {@see ActiveRecord} relation methods such as
     * {@see ActiveRecord::hasOne()} and {@see ActiveRecord::hasMany()}, supporting accurate type inference and IDE
     * autocompletion for dynamic relation definitions.
     *
     * @return string Fully qualified class name of the supported {@see ActiveRecord} class.
     *
     * @phpstan-return class-string
     */
    public function getClass(): string
    {
        return ActiveRecord::class;
    }

    /**
     * Infers the return type for a relation method call on a {@see ActiveRecord} instance based on the provided model
     * class argument.
     *
     * Determines the correct return type for {@see ActiveRecord::hasOne()} and {@see ActiveRecord::hasMany()} relation
     * methods by inspecting the first argument which should be a constant string representing the related model class.
     *
     * @param MethodReflection $methodReflection Reflection instance for the method being analyzed.
     * @param MethodCall $methodCall AST node for the method call expression.
     * @param Scope $scope PHPStan analysis scope for type resolution.
     *
     * @throws ShouldNotHappenException if the method argument is missing or invalid.
     *
     * @return Type Inferred return type for the relation method call.
     */
    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type {
        $arg = $methodCall->getRawArgs()[0] ?? null;

        if ($arg === null || $arg::class !== Arg::class) {
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
                    'Invalid argument provided to method %s' . PHP_EOL
                    . 'Hint: You should use ::class instead of ::className()',
                    $methodReflection->getName(),
                ),
            );
        }

        $modelClass = $constantStrings[0]->getValue();

        return new GenericObjectType(ActiveQuery::class, [new ObjectType($modelClass)]);
    }

    /**
     * Determines whether the given method is supported for dynamic return type inference.
     *
     * Checks if the method name is one of the supported relation methods {@see ActiveRecord::hasOne},
     * {@see ActiveRecord::hasMany} for which this extension provides dynamic return type inference.
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
