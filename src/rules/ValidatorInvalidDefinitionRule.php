<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\rules;

use Closure;
use PhpParser\Node;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\{DependencyTracker, Scope};
use PHPStan\Rules\{IdentifierRuleError, Rule, RuleErrorBuilder};
use PHPStan\Type\{ArrayType, MixedType, ObjectType, ObjectWithoutClassType, Type, TypeCombinator, VerbosityLevel};
use yii\base\Model;
use yii2\extensions\phpstan\exception\Message;

use function is_int;

/**
 * Reports validation rules returned by {@see Model::rules()} whose validator type at key `1` is an array or an object
 * other than a {@see Closure}, which {@see \yii\validators\Validator::createValidator()} looks up as a key of
 * `Validator::$builtInValidators` and so rejects with a {@see \TypeError}.
 *
 * Only `rules()` returns of non-anonymous {@see Model} subclasses that inherit `createValidators()` and
 * `getValidators()` from {@see Model} are analysed. Each rule must be one constant array that native types prove, with a
 * non-null key `0`; types that may be a {@see Closure} or a valid key, unions with such types, incomplete rules and
 * {@see \yii\validators\Validator} instances stand aside.
 *
 * @implements Rule<Return_>
 */
final class ValidatorInvalidDefinitionRule implements Rule
{
    /**
     * Error identifier reported by this rule.
     */
    private const string IDENTIFIER = 'yii2.validator.invalidDefinition';

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
     * Returns one error per returned rule whose validator type can't be a key of `Validator::$builtInValidators`.
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
            $type = self::invalidType($rule);

            if ($type === null) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(
                Message::VALIDATOR_INVALID_DEFINITION->getMessage(
                    is_int($index) ? "#{$index}" : "'{$index}'",
                    $owner->getDisplayName(),
                    self::describe($type),
                ),
            )
            ->identifier(self::IDENTIFIER)
            ->line($line)
            ->build();
        }

        return $errors;
    }

    /**
     * Returns `an array`, the object type, or `an array or` followed by the object type, for a rejected validator type.
     */
    private static function describe(Type $type): string
    {
        $array = new ArrayType(new MixedType(), new MixedType());

        if ($type->isArray()->yes()) {
            return 'an array';
        }

        $objects = TypeCombinator::remove($type, $array)->describe(VerbosityLevel::typeOnly());

        return $type->isArray()->no() ? $objects : "an array or {$objects}";
    }

    /**
     * Returns the validator type of a structurally complete rule when every value it may hold is an array or an object
     * other than a {@see Closure}, or `null` otherwise.
     *
     * Mirrors `createValidators()`, which passes key `1` of an array rule with non-null keys `0` and `1` to
     * `createValidator()`, and its `instanceof Closure` check before the `isset()` lookup that throws.
     */
    private static function invalidType(Type $rule): Type|null
    {
        $values = ModelRulesReader::ruleValues($rule);

        if ($values === null || isset($values[0], $values[1]) === false || $values[0]->isNull()->no() === false) {
            return null;
        }

        $type = $values[1];
        $rejected = TypeCombinator::union(
            new ArrayType(new MixedType(), new MixedType()),
            new ObjectWithoutClassType(),
        );

        return $rejected->isSuperTypeOf($type)->yes() && (new ObjectType(Closure::class))->isSuperTypeOf($type)->no()
            ? $type
            : null;
    }
}
