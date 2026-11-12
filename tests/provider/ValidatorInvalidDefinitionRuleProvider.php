<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use yii\validators\RequiredValidator;
use yii2\extensions\phpstan\tests\data\rule\validator\{
    ConstantInvalidDefinitionListModel,
    CreateValidatorsDefinitionOverrideModel,
    CreateValidatorsOverrideTrait,
    DefinitionInvocable,
    DefinitionKind,
    DefinitionRulesTrait,
    EmptyRulesModel,
    GetValidatorsDefinitionOverrideModel,
    InheritedCreateValidatorsDefinitionModel,
    InheritedGetValidatorsDefinitionModel,
    InvalidDefinitionRulesModel,
    NestedReturnDefinitionRulesModel,
    NotModelDefinitionRules,
    StandAsideDefinitionRulesModel,
    TraitCreateValidatorsDefinitionModel,
    TraitDefinitionRulesModel,
    UnpackedInvalidDefinitionListModel,
    ValidDefinitionRulesModel,
};

use function sprintf;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\rules\ValidatorInvalidDefinitionRuleTest} and
 * {@see \yii2\extensions\phpstan\tests\rules\ValidatorInvalidDefinitionRuntimeTest} test cases.
 */
final class ValidatorInvalidDefinitionRuleProvider
{
    /**
     * Returns the expected message for a rule whose validator type can't be a key of `Validator::$builtInValidators`.
     */
    public static function message(string $rule, string $owner, string $type): string
    {
        return sprintf(
            'Validation rule %s in %s::rules() uses %s as validator type, which Validator::createValidator() looks up '
            . 'as a key of Validator::$builtInValidators: an array or an object other than a Closure throws a '
            . 'TypeError.',
            $rule,
            $owner,
            $type,
        );
    }

    /**
     * @phpstan-return iterable<string, array{class-string, list<array{string, string|null, string}>}>
     */
    public static function reportedProvider(): iterable
    {
        $owner = InvalidDefinitionRulesModel::class;
        $invocable = DefinitionInvocable::class;

        // in the fixture's list order, which is the report order
        yield 'literal list with item lines' => [
            $owner,
            [
                [
                    "['name', ['class' => RequiredValidator::class]],",
                    null,
                    self::message('#1', $owner, 'an array'),
                ],
                [
                    "[['name', 'email'], ['class' => StringValidator::class",
                    null,
                    self::message('#2', $owner, 'an array'),
                ],
                [
                    "['name', [self::class, 'check']],",
                    null,
                    self::message('#3', $owner, 'an array'),
                ],
                [
                    "['name', [\$this, 'check']],",
                    null,
                    self::message('#4', $owner, 'an array'),
                ],
                [
                    "['name', []],",
                    null,
                    self::message('#5', $owner, 'an array'),
                ],
                [
                    "['name', new RequiredValidator()],",
                    null,
                    self::message('#6', $owner, RequiredValidator::class),
                ],
                [
                    "['name', new DefinitionInvocable()],",
                    null,
                    self::message('#7', $owner, $invocable),
                ],
                [
                    "['name', \$this],",
                    null,
                    self::message('#8', $owner, "\$this({$owner})"),
                ],
                [
                    "['name', DefinitionKind::Required],",
                    null,
                    self::message('#9', $owner, DefinitionKind::class . '::Required'),
                ],
                [
                    "['name', \$this->stringable()],",
                    null,
                    self::message('#10', $owner, 'Stringable'),
                ],
                [
                    "['name', \$this->options],",
                    null,
                    self::message('#11', $owner, 'an array'),
                ],
                [
                    "['name', \$this->flag ?",
                    null,
                    self::message('#12', $owner, "an array or {$invocable}"),
                ],
                [
                    "['name', 'trim', 1 => new DefinitionInvocable()],",
                    null,
                    self::message('#13', $owner, $invocable),
                ],
                [
                    "['1' => ['class' => RequiredValidator::class], '0' => 'name'],",
                    null,
                    self::message('#14', $owner, 'an array'),
                ],
                [
                    "'named' => ['name', [RequiredValidator::class]],",
                    null,
                    self::message("'named'", $owner, 'an array'),
                ],
                [
                    'self::CONFIGURED_TYPE,',
                    'return [',
                    self::message('#15', $owner, 'an array'),
                ],
                [
                    '[',
                    'self::CONFIGURED_TYPE,',
                    self::message('#16', $owner, 'an array'),
                ],
            ],
        ];

        $owner = ConstantInvalidDefinitionListModel::class;

        yield 'constant list located at the return' => [
            $owner,
            [
                [
                    'return $rules;',
                    null,
                    self::message('#1', $owner, 'an array'),
                ],
            ],
        ];

        $owner = UnpackedInvalidDefinitionListModel::class;

        yield 'unpacked literal located at the return' => [
            $owner,
            [
                [
                    'return [...self::BASE',
                    null,
                    self::message('#1', $owner, $invocable),
                ],
            ],
        ];
    }

    /**
     * Indexes of the reported rules of each reported fixture model, in list order; its other rules are accepted.
     *
     * @phpstan-return array<class-string<\yii\base\Model>, list<int|string>>
     */
    public static function reportedRules(): array
    {
        return [
            ConstantInvalidDefinitionListModel::class => [1],
            InvalidDefinitionRulesModel::class => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 'named', 15, 16],
            UnpackedInvalidDefinitionListModel::class => [1],
        ];
    }

    /**
     * @phpstan-return iterable<string, array{list<class-string>}>
     */
    public static function standAsideProvider(): iterable
    {
        yield 'class that is not a model' => [[NotModelDefinitionRules::class]];
        yield 'createValidators() override' => [[CreateValidatorsDefinitionOverrideModel::class]];
        yield 'createValidators() override in a trait' => [
            [
                TraitCreateValidatorsDefinitionModel::class,
                CreateValidatorsOverrideTrait::class,
            ],
        ];
        yield 'empty list' => [[EmptyRulesModel::class]];
        yield 'getValidators() override' => [[GetValidatorsDefinitionOverrideModel::class]];
        yield 'inherited createValidators() override' => [[InheritedCreateValidatorsDefinitionModel::class]];
        yield 'inherited getValidators() override' => [[InheritedGetValidatorsDefinitionModel::class]];
        yield 'returns of a closure, an anonymous model and another method' => [
            [NestedReturnDefinitionRulesModel::class],
        ];
        yield 'rules() declared in a trait' => [
            [
                TraitDefinitionRulesModel::class,
                DefinitionRulesTrait::class,
            ],
        ];
        yield 'uncertain, scalar, name, incomplete, union and dynamic rules' => [
            [StandAsideDefinitionRulesModel::class],
        ];
        yield 'Closure, name and class types and validator instances' => [[ValidDefinitionRulesModel::class]];
    }

    /**
     * Fixture models whose `rules()` returns only rules that Yii creates.
     *
     * @phpstan-return list<class-string<\yii\base\Model>>
     */
    public static function validModels(): array
    {
        return [
            EmptyRulesModel::class,
            ValidDefinitionRulesModel::class,
        ];
    }
}
