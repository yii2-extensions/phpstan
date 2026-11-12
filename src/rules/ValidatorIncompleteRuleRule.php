<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\rules;

use PhpParser\Node;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\{DependencyTracker, Scope};
use PHPStan\Rules\{IdentifierRuleError, Rule, RuleErrorBuilder};
use PHPStan\Type\Type;
use yii\base\Model;
use yii2\extensions\phpstan\exception\Message;

use function implode;
use function is_int;

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
        $owner = ModelRulesReader::owner($node, $scope);

        if ($owner === null || $node->expr === null) {
            return [];
        }

        $errors = [];

        foreach (
            ModelRulesReader::entries($node->expr, $node->getStartLine(), $scope) ?? [] as [$index, $line, $rule]
        ) {
            $defects = self::defects($rule);

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
        $values = ModelRulesReader::ruleValues($rule);

        if ($values === null) {
            return null;
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
}
