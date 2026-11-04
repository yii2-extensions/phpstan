<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\rules;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Type;
use yii\BaseYii;

use function count;

/**
 * Reads the options of a Yii object configuration whose keys are proven by literals or by PHPStan's native types.
 *
 * {@see BaseYii::configure()} for the assignment of each option.
 */
final class ObjectConfigurationReader
{
    /**
     * Returns the only constant array a native type can hold, without optional keys or an unsealed part.
     *
     * A single constant array doesn't prove the value on its own: an intersection can synthesize one for a callable
     * array, so the array must also contain every value of the type.
     */
    public static function constantArray(Type $type): ConstantArrayType|null
    {
        $arrays = $type->getConstantArrays();

        if (count($arrays) !== 1) {
            return null;
        }

        $array = $arrays[0];

        return $array->isSuperTypeOf($type)->yes()
            && $array->getOptionalKeys() === []
            && $array->isUnsealed()->yes() === false
            ? $array
            : null;
    }

    /**
     * Returns the value of a native type that holds exactly one constant string.
     */
    public static function constantString(Type $type): string|null
    {
        $strings = $type->getConstantStrings();

        return count($strings) === 1 && $strings[0]->isSuperTypeOf($type)->yes() ? $strings[0]->getValue() : null;
    }

    /**
     * Returns the first line of each key of an array literal without unpacking, by option name.
     *
     * @return array<int|string, int> Line by option name; empty when the expression isn't such a literal or a key
     * doesn't have one constant string value, so that no option is located at an item it may not come from.
     */
    public static function keyLines(Expr $config, Scope $scope): array
    {
        if ($config instanceof Array_ === false) {
            return [];
        }

        $lines = [];

        foreach ($config->items as $item) {
            $name = match (true) {
                $item->key === null => null,
                $item->key instanceof String_ => $item->key->value,
                default => self::constantString($scope->getNativeType($item->key)),
            };

            if ($name === null) {
                return [];
            }

            $lines[$name] ??= $item->getStartLine();
        }

        return $lines;
    }

    /**
     * Returns the distinct options of a configuration, in first-occurrence order, each with the line it is reported at
     * and its effective value, as PHP builds the array.
     *
     * An array literal without unpacking whose keys are all literal strings yields the value expression of the last
     * item of each key, located at its first item. Any other configuration must have a native type that is one constant
     * array with string keys; it yields the value types, located at their first key when the configuration is an array
     * literal without unpacking whose keys all have one constant string value, otherwise at the given line.
     *
     * @param Expr $config Configuration expression.
     * @param int $callLine Line of the call, for options the configuration doesn't locate.
     *
     * @return list<array{string, int, Expr|Type}>|null Name, line and effective value of each option, or `null` when
     * neither form proves the configuration, including a native type with more than one array, an optional key or an
     * integer key.
     */
    public static function options(Expr $config, int $callLine, Scope $scope): array|null
    {
        return self::isLiteral($config)
            ? self::literalOptions($config)
            : self::constantOptions($config, $callLine, $scope);
    }

    /**
     * Returns the options of a configuration whose native type is one constant array with string keys.
     *
     * @return list<array{string, int, Type}>|null
     */
    private static function constantOptions(Expr $config, int $callLine, Scope $scope): array|null
    {
        $array = self::constantArray($scope->getNativeType($config));

        if ($array === null) {
            return null;
        }

        $lines = self::keyLines($config, $scope);
        $options = [];

        foreach ($array->getKeyTypes() as $keyType) {
            $name = self::constantString($keyType);

            if ($name === null) {
                return null;
            }

            $options[] = [$name, $lines[$name] ?? $callLine, $array->getOffsetValueType($keyType)];
        }

        return $options;
    }

    /**
     * Returns whether the configuration is an array literal without unpacking whose keys are all literal strings.
     *
     * @phpstan-assert-if-true Array_ $config
     */
    private static function isLiteral(Expr $config): bool
    {
        if ($config instanceof Array_ === false) {
            return false;
        }

        foreach ($config->items as $item) {
            if ($item->unpack || $item->key instanceof String_ === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns the distinct options of an array literal whose keys are all literal strings.
     *
     * @return list<array{string, int, Expr}>
     */
    private static function literalOptions(Array_ $config): array
    {
        $options = [];
        $positions = [];

        foreach ($config->items as $item) {
            $name = $item->key instanceof String_ ? $item->key->value : '';
            $position = $positions[$name] ?? null;

            if ($position !== null && isset($options[$position])) {
                $options[$position] = [$options[$position][0], $options[$position][1], $item->value];
            } else {
                $positions[$name] = count($options);
                $options[] = [$name, $item->getStartLine(), $item->value];
            }
        }

        return $options;
    }
}
