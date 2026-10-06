<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan;

use PHPStan\TrinaryLogic;
use PHPStan\Type\{ArrayType, BooleanType, FloatType, IntegerType, MixedType, NullType, StringType, Type};
use PHPStan\Type\Constant\{ConstantArrayType, ConstantIntegerType, ConstantStringType};
use yii2\extensions\phpstan\type\ApplicationTypeNodeResolverExtension;

use function array_is_list;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;
use function max;

/**
 * Builds the PHPStan array shape type of Yii application params from their configured values.
 *
 * Scalars are generalized to `string`, `int`, `float` and `bool`, `null` stays `null`, nested arrays become nested
 * shapes (lists become positional shapes), empty arrays become `array<mixed, mixed>`, and any other value becomes
 * explicit `mixed`, matching what the same shape written as a PHPDoc type resolves to. Shapes are never degraded to
 * general arrays, regardless of the number of keys, which is also how PHPStan resolves a PHPDoc array shape.
 *
 * {@see ServiceMap::getParams()} for the configured params.
 * {@see ApplicationTypeNodeResolverExtension} for the `Module::$params` type resolved from this shape.
 */
final class ParamsTypeBuilder
{
    /**
     * Builds the array shape type for the given params.
     *
     * @param array<array-key, mixed> $params Configured params, as returned by {@see ServiceMap::getParams()}.
     *
     * @return Type Array shape type, or `array<mixed, mixed>` for an empty array.
     */
    public static function build(array $params): Type
    {
        if ($params === []) {
            return new ArrayType(new MixedType(true), new MixedType(true));
        }

        $keyTypes = [];
        $valueTypes = [];
        $nextAutoIndex = 0;

        foreach ($params as $key => $value) {
            if (is_int($key)) {
                $keyTypes[] = new ConstantIntegerType($key);
                $nextAutoIndex = max($nextAutoIndex, $key + 1);
            } else {
                $keyTypes[] = new ConstantStringType($key);
            }

            $valueTypes[] = self::buildValueType($value);
        }

        // PHP array keys are unique, so the shape is built in one pass instead of through `ConstantArrayTypeBuilder`,
        // whose duplicate key lookup makes large params quadratic
        return new ConstantArrayType(
            $keyTypes,
            $valueTypes,
            [$nextAutoIndex],
            [],
            TrinaryLogic::createFromBoolean(array_is_list($params)),
        );
    }

    /**
     * Builds the type of a single params value.
     */
    private static function buildValueType(mixed $value): Type
    {
        return match (true) {
            $value === null => new NullType(),
            is_string($value) => new StringType(),
            is_int($value) => new IntegerType(),
            is_float($value) => new FloatType(),
            is_bool($value) => new BooleanType(),
            is_array($value) => self::build($value),
            default => new MixedType(true),
        };
    }
}
