<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use yii2\extensions\phpstan\tests\data\rule\validator\{
    ConstantListRulesModel,
    CreateValidatorsOverrideModel,
    DynamicRulesModel,
    EmptyRulesModel,
    GetValidatorsOverrideModel,
    InheritedCreateValidatorsModel,
    InheritedGetValidatorsModel,
    KeyedRulesModel,
    NestedReturnRulesModel,
    NotModelRules,
    ReportedRulesModel,
    RulesListTrait,
    StandAsideRulesModel,
    TraitCreateValidatorsModel,
    TraitRulesModel,
    UnpackedListRulesModel,
    ValidRulesModel,
};

use function sprintf;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\rules\ValidatorIncompleteRuleRuleTest} and
 * {@see \yii2\extensions\phpstan\tests\rules\ValidatorIncompleteRuleRuntimeTest} test cases.
 */
final class ValidatorIncompleteRuleRuleProvider
{
    /**
     * Returns the expected message for an incomplete rule.
     */
    public static function message(string $rule, string $owner, string $defects): string
    {
        return sprintf(
            'Validation rule %s in %s::rules() has %s, which Model::createValidators() rejects: a rule must specify the '
            . 'attribute names at key 0 and the validator type at key 1.',
            $rule,
            $owner,
            $defects,
        );
    }

    /**
     * @phpstan-return iterable<string, array{class-string, list<array{string, string|null, string}>}>
     */
    public static function reportedProvider(): iterable
    {
        $owner = ReportedRulesModel::class;

        // in the fixture's list order, which is the report order
        yield 'literal list with item lines' => [
            $owner,
            [
                [
                    "['name'],",
                    null,
                    self::message(
                        '#0',
                        $owner,
                        'no key 1',
                    ),
                ],
                [
                    "[['name', 'email'], 'on' => 'create'],",
                    null,
                    self::message(
                        '#1',
                        $owner,
                        'no key 1',
                    ),
                ],
                [
                    "['name', null],",
                    null,
                    self::message(
                        '#2',
                        $owner,
                        'a null key 1',
                    ),
                ],
                [
                    "[null, 'required'],",
                    null,
                    self::message(
                        '#3',
                        $owner,
                        'a null key 0',
                    ),
                ],
                [
                    "['name', 'validator' => 'required'],",
                    null,
                    self::message(
                        '#4',
                        $owner,
                        'no key 1',
                    ),
                ],
                [
                    "['attributes' => 'name', 'required'],",
                    null,
                    self::message(
                        '#5',
                        $owner,
                        'no key 1',
                    ),
                ],
                [
                    '[],',
                    null,
                    self::message(
                        '#6',
                        $owner,
                        'no key 0 and no key 1',
                    ),
                ],
                [
                    '[null],',
                    null,
                    self::message(
                        '#7',
                        $owner,
                        'a null key 0 and no key 1',
                    ),
                ],
                [
                    "[1 => 'required'],",
                    null,
                    self::message(
                        '#8',
                        $owner,
                        'no key 0',
                    ),
                ],
                [
                    "['1' => 'required',",
                    null,
                    self::message(
                        '#9',
                        $owner,
                        'no key 0',
                    ),
                ],
                [
                    "['name', 'required', 1 => null],",
                    null,
                    self::message(
                        '#10',
                        $owner,
                        'a null key 1',
                    ),
                ],
                [
                    'self::MISSING_TYPE,',
                    null,
                    self::message(
                        '#11',
                        $owner,
                        'no key 1',
                    ),
                ],
                [
                    '[',
                    'self::MISSING_TYPE,',
                    self::message(
                        '#12',
                        $owner,
                        'no key 1',
                    ),
                ],
            ],
        ];

        $owner = KeyedRulesModel::class;

        yield 'explicit, negative, normalized and duplicate keys' => [
            $owner,
            [
                [
                    "'named' => ['named'],",
                    null,
                    self::message(
                        "'named'",
                        $owner,
                        'no key 1',
                    ),
                ],
                [
                    '-3 =>',
                    null,
                    self::message(
                        '#-3',
                        $owner,
                        'no key 1',
                    ),
                ],
                [
                    '-2 =>',
                    null,
                    self::message(
                        '#-2',
                        $owner,
                        'a null key 0',
                    ),
                ],
                [
                    "'7' =>",
                    null,
                    self::message(
                        '#7',
                        $owner,
                        'no key 1',
                    ),
                ],
            ],
        ];

        $owner = ConstantListRulesModel::class;

        yield 'constant list located at the return' => [
            $owner,
            [
                [
                    'return $rules;',
                    null,
                    self::message(
                        '#1',
                        $owner,
                        'no key 1',
                    ),
                ],
            ],
        ];

        $owner = UnpackedListRulesModel::class;

        yield 'unpacked literal located at the return' => [
            $owner,
            [
                [
                    'return [...self::BASE',
                    null,
                    self::message(
                        '#1',
                        $owner,
                        'a null key 0',
                    ),
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
            ConstantListRulesModel::class => [1],
            KeyedRulesModel::class => ['named', -3, -2, 7],
            ReportedRulesModel::class => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
            UnpackedListRulesModel::class => [1],
        ];
    }

    /**
     * @phpstan-return iterable<string, array{list<class-string>}>
     */
    public static function standAsideProvider(): iterable
    {
        yield 'class that is not a model' => [[NotModelRules::class]];
        yield 'createValidators() override' => [[CreateValidatorsOverrideModel::class]];
        yield 'createValidators() override in a trait' => [[TraitCreateValidatorsModel::class]];
        yield 'empty list' => [[EmptyRulesModel::class]];
        yield 'getValidators() override' => [[GetValidatorsOverrideModel::class]];
        yield 'inherited createValidators() override' => [[InheritedCreateValidatorsModel::class]];
        yield 'inherited getValidators() override' => [[InheritedGetValidatorsModel::class]];
        yield 'merged, built and dynamically keyed lists' => [[DynamicRulesModel::class]];
        yield 'returns of a closure, an anonymous model and another method' => [[NestedReturnRulesModel::class]];
        yield 'rules() declared in a trait' => [[TraitRulesModel::class, RulesListTrait::class]];
        yield 'unproven, union, non-array and validator rules' => [[StandAsideRulesModel::class]];
        yield 'valid rules' => [[ValidRulesModel::class]];
    }

    /**
     * Fixture models whose `rules()` returns only rules that Yii accepts.
     *
     * @phpstan-return list<class-string<\yii\base\Model>>
     */
    public static function validModels(): array
    {
        return [
            EmptyRulesModel::class,
            ValidRulesModel::class,
        ];
    }
}
