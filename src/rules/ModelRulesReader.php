<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\rules;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\{DependencyTracker, Scope};
use PHPStan\Reflection\{ClassReflection, MethodReflection};
use PHPStan\Type\Type;
use yii\base\Model;

use function count;
use function is_int;
use function is_string;
use function strtolower;

/**
 * Reads the validation rules that a {@see Model::rules()} return passes unchanged to {@see Model::createValidators()},
 * as proven by literals or by PHPStan's native types.
 */
final class ModelRulesReader
{
    /**
     * Returns the entries of a rule list as `[index, line, rule type]`, in list order, or `null` when the list isn't
     * certain.
     *
     * Literal items keep their lines, with PHP's key normalization, implicit keys (after a negative key, its successor)
     * and last-duplicate semantics; other lists, and literals with unpacking or non-constant keys, must be one constant
     * array and use the return line.
     *
     * @param Expr $list Returned rule list.
     * @param int $line Line of the return statement.
     * @param Scope $scope Current PHPStan analysis scope.
     *
     * @return list<array{int|string, int, Type}>|null Native type of each rule, or `null` when the list isn't proven.
     */
    public static function entries(Expr $list, int $line, Scope $scope): array|null
    {
        if ($list instanceof Array_ === false) {
            return self::typeEntries($scope->getNativeType($list), $line);
        }

        $entries = [];
        $positions = [];
        $next = null;

        foreach ($list->items as $item) {
            $key = $item->key === null ? $next ?? 0 : self::literalKey($scope->getNativeType($item->key));

            if ($item->unpack || $key === null) {
                return self::typeEntries($scope->getNativeType($list), $line);
            }

            // since PHP 8.3 the next implicit key follows the first integer key, even a negative one
            if (is_int($key) && ($next === null || $key >= $next)) {
                $next = $key + 1;
            }

            $entry = [$key, $item->getStartLine(), $scope->getNativeType($item->value)];

            if (isset($positions[$key])) {
                $entries[$positions[$key]] = $entry;
            } else {
                $positions[$key] = count($entries);
                $entries[] = $entry;
            }
        }

        return $entries;
    }

    /**
     * Returns the class whose `rules()` returns the statement when {@see Model} consumes it unchanged, or `null`.
     *
     * Only non-anonymous {@see Model} subclasses that inherit `createValidators()` and `getValidators()` from
     * {@see Model} qualify; returns in traits and closures don't. Records the declaration dependency of the owner before
     * checking its `createValidators()` and `getValidators()`, so that a transition out of an override reanalyses it.
     *
     * @param Return_ $node Return statement to inspect.
     * @param DependencyTracker&Scope $scope Current PHPStan analysis scope.
     */
    public static function owner(Return_ $node, Scope $scope): ClassReflection|null
    {
        if ($node->expr === null || $scope->isInAnonymousFunction() || $scope->isInTrait()) {
            return null;
        }

        $function = $scope->getFunction();
        $owner = $scope->getClassReflection();

        if (
            $function instanceof MethodReflection === false
            || $owner === null
            || strtolower($function->getName()) !== 'rules'
            || $owner->isClass() === false
            || $owner->isAnonymous()
            || $owner->getName() === Model::class
            || $owner->is(Model::class) === false
        ) {
            return null;
        }

        $scope->trackClassDependency($owner->getName());

        return self::isDeclaredByModel($owner, 'createValidators') && self::isDeclaredByModel($owner, 'getValidators')
            ? $owner
            : null;
    }

    /**
     * Returns the value type at each key of a rule that native types prove to be one constant array without optional
     * keys, or `null` for any other rule, such as a {@see \yii\validators\Validator} instance or a union.
     *
     * @param Type $rule Native type of the rule.
     *
     * @return array<int|string, Type>|null
     */
    public static function ruleValues(Type $rule): array|null
    {
        $array = ObjectConfigurationReader::constantArray($rule);

        if ($array === null) {
            return null;
        }

        $values = [];

        foreach ($array->getKeyTypes() as $keyType) {
            $values[$keyType->getValue()] = $array->getOffsetValueType($keyType);
        }

        return $values;
    }

    /**
     * Returns whether {@see Model} declares the method that the class inherits.
     */
    private static function isDeclaredByModel(ClassReflection $class, string $method): bool
    {
        return $class->hasNativeMethod($method)
            && $class->getNativeMethod($method)->getDeclaringClass()->getName() === Model::class;
    }

    /**
     * Returns the array key that PHP stores for a literal key, or `null` when it isn't one `int` or `string` constant.
     */
    private static function literalKey(Type $type): int|string|null
    {
        $values = $type->getConstantScalarValues();

        if (count($values) !== 1 || (is_int($values[0]) === false && is_string($values[0]) === false)) {
            return null;
        }

        $key = $values[0];

        return is_string($key) && (string) (int) $key === $key ? (int) $key : $key;
    }

    /**
     * Returns the entries of a list proven as one constant array, all at the given line, or `null` otherwise.
     *
     * @return list<array{int|string, int, Type}>|null
     */
    private static function typeEntries(Type $type, int $line): array|null
    {
        $array = ObjectConfigurationReader::constantArray($type);

        if ($array === null) {
            return null;
        }

        $entries = [];

        foreach ($array->getKeyTypes() as $keyType) {
            $entries[] = [$keyType->getValue(), $line, $array->getOffsetValueType($keyType)];
        }

        return $entries;
    }
}
