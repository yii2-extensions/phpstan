<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\rules;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\{DependencyTracker, Scope};
use PHPStan\Reflection\{ClassReflection, MethodReflection};
use PHPStan\Rules\{IdentifierRuleError, Rule, RuleErrorBuilder};
use PHPStan\Type\Type;
use yii\base\Model;
use yii2\extensions\phpstan\exception\Message;

use function count;
use function implode;
use function is_int;
use function is_string;
use function strtolower;

/**
 * Reports validation rules returned by {@see Model::rules()} that {@see Model::createValidators()} rejects because the
 * rule has no non-null attribute names at key `0` or validator type at key `1`.
 *
 * Only `rules()` returns of non-anonymous {@see Model} subclasses that inherit `createValidators()` and
 * `getValidators()` from {@see Model} are analysed. Each rule must be one constant array that native types prove; rules
 * whose key `0` or `1` may be `null`, unions, dynamic lists, {@see \yii\validators\Validator} instances and non-array
 * rules stand aside.
 *
 * @implements Rule<Return_>
 */
final class ValidatorIncompleteRuleRule implements Rule
{
    /**
     * Error identifier reported by this rule.
     */
    private const string IDENTIFIER = 'yii2.validator.incompleteRule';

    /**
     * Returns the node class this rule analyses.
     *
     * @return class-string<Return_>
     */
    public function getNodeType(): string
    {
        return Return_::class;
    }

    /**
     * Returns one error per returned rule that lacks a non-null key `0` or `1`.
     *
     * Records the declaration dependency of the owner, also when its overrides make the rule stand aside.
     *
     * @param Return_ $node Return statement to inspect.
     * @param DependencyTracker&Scope $scope Current PHPStan analysis scope.
     *
     * @return list<IdentifierRuleError> Errors reported at the list item, else at the return line.
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $owner = self::owner($node, $scope);

        if ($owner === null || $node->expr === null) {
            return [];
        }

        $errors = [];

        foreach (self::entries($node->expr, $node->getStartLine(), $scope) ?? [] as [$index, $line, $rule]) {
            $defects = self::defects($rule instanceof Expr ? $scope->getNativeType($rule) : $rule);

            if ($defects === null) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(
                Message::VALIDATOR_INCOMPLETE_RULE->getMessage(
                    is_int($index) ? "#{$index}" : "'{$index}'",
                    $owner->getDisplayName(),
                    $defects,
                ),
            )
            ->identifier(self::IDENTIFIER)
            ->line($line)
            ->build();
        }

        return $errors;
    }

    /**
     * Returns the defects of a rule that `createValidators()` certainly rejects, such as `no key 1`, or `null` when the
     * rule isn't one constant array, holds both keys with non-null values, or may hold `null` at one of them.
     *
     * Mirrors `is_array($rule) && isset($rule[0], $rule[1])`; a non-array rule is left to the declared `rules()` type.
     */
    private static function defects(Type $rule): string|null
    {
        $array = ObjectConfigurationReader::constantArray($rule);

        if ($array === null) {
            return null;
        }

        $values = [];

        foreach ($array->getKeyTypes() as $keyType) {
            $values[$keyType->getValue()] = $array->getOffsetValueType($keyType);
        }

        $defects = [];

        foreach ([0, 1] as $key) {
            $value = $values[$key] ?? null;

            if ($value === null) {
                $defects[] = "no key {$key}";
            } elseif ($value->isNull()->yes()) {
                $defects[] = "a null key {$key}";
            } elseif ($value->isNull()->maybe()) {
                return null;
            }
        }

        return $defects === [] ? null : implode(' and ', $defects);
    }

    /**
     * Returns the entries of a rule list as `[index, line, rule]`, in list order, or `null` when the list isn't certain.
     *
     * Literal items keep their lines, with PHP's key normalization, implicit keys (after a negative key, its successor)
     * and last-duplicate semantics; other lists, and literals with unpacking or non-constant keys, must be one constant
     * array and use the return line.
     *
     * @return list<array{int|string, int, Expr|Type}>|null
     */
    private static function entries(Expr $list, int $line, Scope $scope): array|null
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

            $entry = [$key, $item->getStartLine(), $item->value];

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
     * Returns the class whose `rules()` returns the statement when {@see Model} consumes it unchanged, or `null`.
     *
     * Records the declaration dependency of the owner before checking its `createValidators()` and `getValidators()`.
     *
     * @param DependencyTracker&Scope $scope Current PHPStan analysis scope.
     */
    private static function owner(Return_ $node, Scope $scope): ClassReflection|null
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
