<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use ReflectionClass;
use RuntimeException;
use yii2\extensions\phpstan\tests\data\rule\{
    CreateObjectComponentOption,
    CreateObjectConstantInvalidOptionType,
    CreateObjectConstructorArgument,
    CreateObjectContainerBinding,
    CreateObjectInvalidOptionType,
};
use yii2\extensions\phpstan\tests\support\FixtureMethod;

use function array_diff;
use function array_keys;
use function implode;
use function sprintf;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\rules\CreateObjectInvalidOptionTypeRuleTest} test cases.
 */
final class CreateObjectInvalidOptionTypeRuleProvider
{
    /**
     * Namespace of the configurable target fixtures.
     */
    private const string TARGETS = 'yii2\extensions\phpstan\tests\support\createobject\\';

    /**
     * @phpstan-return iterable<string, array{class-string, string, list<array{string|null, string}>}>
     */
    public static function reportedProvider(): iterable
    {
        foreach (self::reported() as $fixture => $methods) {
            $names = FixtureMethod::names($fixture);
            $unknown = array_diff(array_keys($methods), $names);

            if ($unknown !== []) {
                throw new RuntimeException(
                    sprintf('Expectations of "%s" name missing methods: %s.', $fixture, implode(', ', $unknown)),
                );
            }

            foreach ($names as $method) {
                yield (new ReflectionClass($fixture))->getShortName() . "::{$method}" => [
                    $fixture,
                    $method,
                    $methods[$method] ?? [],
                ];
            }
        }
    }

    /**
     * Returns the expected message for an option whose value Yii passes to a member of a fixture class.
     */
    private static function message(
        string $calledClass,
        string $targetClass,
        string $option,
        string $value,
        string $member,
        string $expected,
    ): string {
        return sprintf(
            '%s::createObject() sets %s::$%s to %s, but %s accepts only %s, even with type coercion.',
            $calledClass,
            self::TARGETS . $targetClass,
            $option,
            $value,
            $member,
            $expected,
        );
    }

    /**
     * Returns the expected message for an option that Yii assigns to a public property.
     */
    private static function property(
        string $targetClass,
        string $option,
        string $value,
        string $expected,
        string $calledClass = 'Yii',
    ): string {
        return self::message($calledClass, $targetClass, $option, $value, 'the property', $expected);
    }

    /**
     * Returns the expected diagnostics of each fixture method; a method without diagnostics is omitted.
     *
     * @phpstan-return array<class-string, array<string, list<array{string|null, string}>>>
     */
    private static function reported(): array
    {
        return [
            CreateObjectComponentOption::class => [
                'arrayForStringProperty' => [
                    [
                        "'name'",
                        self::property(
                            'ComponentTarget',
                            'name',
                            'array{}',
                            'string',
                        ),
                    ],
                ],
                'behaviorBeforeInvalidValue' => [
                    [
                        "'count'",
                        self::property(
                            'ComponentMembersTarget',
                            'count',
                            "'abc'",
                            'int',
                        ),
                    ],
                ],
                'behaviorsOverrideInvalidValue' => [
                    [
                        "'count'",
                        self::property(
                            'ComponentBehaviorsTarget',
                            'count',
                            "'abc'",
                            'int',
                        ),
                    ],
                ],
                'protectedPropertyUsesSetter' => [
                    [
                        "'guarded'",
                        self::setter(
                            'ComponentMembersTarget',
                            'guarded',
                            "'abc'",
                            'ComponentMembersTarget::setGuarded()',
                            'int',
                        ),
                    ],
                ],
                'stringForIntProperty' => [
                    ["'count'", self::property(
                        'ComponentMembersTarget',
                        'count',
                        "'abc'",
                        'int',
                    )],
                ],
                'stringForIntSetter' => [
                    [
                        "'limit'",
                        self::setter(
                            'ComponentMembersTarget',
                            'limit',
                            "'abc'",
                            'ComponentMembersTarget::setLimit()',
                            'int',
                        ),
                    ],
                ],
            ],
            CreateObjectConstantInvalidOptionType::class => [
                'assignedOffset' => [
                    [
                        null,
                        self::property(
                            'TypedTarget',
                            'count',
                            'array{}',
                            'int',
                        ),
                    ],
                ],
                'constantConfiguration' => [
                    [
                        null,
                        self::property(
                            'TypedTarget',
                            'count',
                            "'abc'",
                            'int',
                        ),
                    ],
                ],
                'localValue' => [
                    [
                        "'count'",
                        self::property(
                            'TypedTarget',
                            'count',
                            "'abc'",
                            'int',
                        ),
                    ],
                ],
                'unpackedConstantThenLiteral' => [
                    [
                        "'count'",
                        self::property(
                            'TypedTarget',
                            'count',
                            "'abc'",
                            'int',
                        ),
                    ],
                ],
                'variableConfiguration' => [
                    [
                        null,
                        self::setter(
                            'TypedTarget',
                            'tags',
                            "'abc'",
                            'TypedTarget::setTags()',
                            'array',
                        ),
                    ],
                ],
            ],
            CreateObjectConstructorArgument::class => [
                'componentParamsInvalidValue' => [
                    [
                        "'count'",
                        self::property(
                            'ComponentMembersTarget',
                            'count',
                            "'abc'",
                            'int',
                        ),
                    ],
                ],
                'constructInvalidValue' => [
                    [
                        "'count'",
                        self::property(
                            'TypedTarget',
                            'count',
                            "'abc'",
                            'int',
                        ),
                    ],
                ],
                'definitionParamsInvalidValue' => [
                    [
                        "'count'",
                        self::property(
                            'TypedTarget',
                            'count',
                            "'abc'",
                            'int',
                        ),
                    ],
                ],
                'definitionParamsWithOptionsInvalidValue' => [
                    [
                        "'count'",
                        self::property(
                            'TypedTarget',
                            'count',
                            "'abc'",
                            'int',
                        ),
                    ],
                ],
                'optionsInstanceInvalidValue' => [
                    [
                        "'items'",
                        self::property(
                            'TypedTarget',
                            'items',
                            'yii\\di\\Instance',
                            'array',
                        ),
                    ],
                ],
                'paramsIgnoredForOptionsInvalidValue' => [
                    [
                        "'count'",
                        self::property(
                            'TypedTarget',
                            'count',
                            "'abc'",
                            'int',
                        ),
                    ],
                ],
                'paramsInvalidValue' => [
                    [
                        "'count'",
                        self::property(
                            'TypedTarget',
                            'count',
                            "'abc'",
                            'int',
                        ),
                    ],
                ],
            ],
            CreateObjectContainerBinding::class => [
                'componentAliasInvalidValue' => [
                    [
                        "'count'",
                        self::property(
                            'ComponentMembersTarget',
                            'count',
                            "'abc'",
                            'int',
                        ),
                    ],
                ],
                'componentDefinitionOptionsInvalidValue' => [
                    [
                        "'count'",
                        self::property(
                            'ComponentMembersTarget',
                            'count',
                            "'abc'",
                            'int',
                        ),
                    ],
                ],
                'definitionOptionsInvalidValue' => [
                    [
                        "'name'",
                        self::property(
                            'Target',
                            'name',
                            'array{}',
                            'string',
                        ),
                    ],
                ],
                'definitionOptionsOnChainInvalidValue' => [
                    [
                        "'tag'",
                        self::property(
                            'Target',
                            'tag',
                            'array{}',
                            'string',
                        ),
                    ],
                ],
                'definitionOptionsWithParamsInvalidValue' => [
                    [
                        "'name'",
                        self::property(
                            'Target',
                            'name',
                            'array{}',
                            'string',
                        ),
                    ],
                ],
                'emptyDefinitionInvalidValue' => [
                    [
                        "'name'",
                        self::property(
                            'EmptyDefinitionTarget',
                            'name',
                            'array{}',
                            'string',
                        ),
                    ],
                ],
                'interfaceAliasInvalidValue' => [
                    [
                        "'name'",
                        self::property(
                            'Target',
                            'name',
                            'array{}',
                            'string',
                        ),
                    ],
                ],
                'threeHopChainInvalidValue' => [
                    [
                        "'name'",
                        self::property(
                            'Target',
                            'name',
                            'array{}',
                            'string',
                        ),
                    ],
                ],
            ],
            CreateObjectInvalidOptionType::class => [
                'arrayForBoolProperty' => [
                    [
                        "'enabled'",
                        self::property(
                            'TypedTarget',
                            'enabled',
                            'array{}',
                            'bool',
                        ),
                    ],
                ],
                'arrayForIntOrStringProperty' => [
                    [
                        "'key'",
                        self::property(
                            'TypedTarget',
                            'key',
                            'array{}',
                            'int|string',
                        ),
                    ],
                ],
                'arrayForIntProperty' => [
                    [
                        "'count'",
                        self::property(
                            'TypedTarget',
                            'count',
                            'array{}',
                            'int',
                        ),
                    ],
                ],
                'arrayForNullableStringSetter' => [
                    [
                        "'label'",
                        self::setter(
                            'TypedTarget',
                            'label',
                            'array{}',
                            'TypedTarget::setLabel()',
                            'string|null',
                        ),
                    ],
                ],
                'arrayForPublicPropertyBeforeSetter' => [
                    [
                        "'both'",
                        self::property(
                            'TypedTarget',
                            'both',
                            'array{}',
                            'int',
                        ),
                    ],
                ],
                'arrayForStringProperty' => [
                    [
                        "'title'",
                        self::property(
                            'TypedTarget',
                            'title',
                            'array{}',
                            'string',
                        ),
                    ],
                ],
                'arrayForStringSetter' => [
                    [
                        "'text'",
                        self::setter(
                            'TypedTarget',
                            'text',
                            'array{}',
                            'TypedTarget::setText()',
                            'string',
                        ),
                    ],
                ],
                'constantKey' => [
                    [
                        'TypedOptions::KEY',
                        self::property(
                            'TypedTarget',
                            'count',
                            "'abc'",
                            'int',
                        ),
                    ],
                ],
                'enumCaseForIntProperty' => [
                    [
                        "'count'",
                        self::property(
                            'TypedTarget',
                            'count',
                            self::TARGETS . 'TypedSuit::Hearts',
                            'int',
                        ),
                    ],
                ],
                'enumConstantForIntProperty' => [
                    [
                        "'count'",
                        self::property(
                            'TypedTarget',
                            'count',
                            self::TARGETS . 'TypedSuit::Hearts',
                            'int',
                        ),
                    ],
                ],
                'finalClassForInterfaceProperty' => [
                    [
                        "'contract'",
                        self::property(
                            'TypedTarget',
                            'contract',
                            self::TARGETS . 'TypedValue',
                            self::TARGETS . 'TypedContract|null',
                        ),
                    ],
                ],
                'inheritedProperty' => [
                    [
                        "'count'",
                        self::property(
                            'TypedChildTarget',
                            'count',
                            "'abc'",
                            'int',
                        ),
                    ],
                ],
                'lastDuplicateValue' => [
                    [
                        "'count'",
                        self::property(
                            'TypedTarget',
                            'count',
                            "'abc'",
                            'int',
                        ),
                    ],
                ],
                'leadingNumericStringForFloatProperty' => [
                    [
                        "'ratio'",
                        self::property(
                            'TypedTarget',
                            'ratio',
                            "'1abc'",
                            'float',
                        ),
                    ],
                ],
                'multilineKey' => [
                    [
                        "'count'",
                        self::property(
                            'TypedTarget',
                            'count',
                            "'abc'",
                            'int',
                        ),
                    ],
                ],
                'namedArguments' => [
                    [
                        "'count'",
                        self::property(
                            'TypedTarget',
                            'count',
                            "'abc'",
                            'int',
                            'yii\BaseYii',
                        ),
                    ],
                ],
                'nonNumericStringForIntProperty' => [
                    [
                        "'count'",
                        self::property(
                            'TypedTarget',
                            'count',
                            "'abc'",
                            'int',
                        ),
                    ],
                ],
                'nonNumericStringForNullableIntProperty' => [
                    [
                        "'limit'",
                        self::property(
                            'TypedTarget',
                            'limit',
                            "'abc'",
                            'int|null',
                        ),
                    ],
                ],
                'nullForIntProperty' => [
                    [
                        "'count'",
                        self::property(
                            'TypedTarget',
                            'count',
                            'null',
                            'int',
                        ),
                    ],
                ],
                'nullForStringProperty' => [
                    [
                        "'title'",
                        self::property(
                            'TypedTarget',
                            'title',
                            'null',
                            'string',
                        ),
                    ],
                ],
                'objectForBoolSetter' => [
                    [
                        "'flag'",
                        self::setter(
                            'TypedTarget',
                            'flag',
                            self::TARGETS . 'TypedPlain',
                            'TypedTarget::setFlag()',
                            'bool',
                        ),
                    ],
                ],
                'objectForFinalClassProperty' => [
                    [
                        "'value'",
                        self::property(
                            'TypedTarget',
                            'value',
                            'stdClass',
                            self::TARGETS . 'TypedValue|null',
                        ),
                    ],
                ],
                'objectForIntProperty' => [
                    [
                        "'count'",
                        self::property(
                            'TypedTarget',
                            'count',
                            self::TARGETS . 'TypedPlain',
                            'int',
                        ),
                    ],
                ],
                'ownPropertyOfChild' => [
                    [
                        "'weight'",
                        self::property(
                            'TypedChildTarget',
                            'weight',
                            "'abc'",
                            'float',
                        ),
                    ],
                ],
                'protectedPropertyUsesSetter' => [
                    [
                        "'guardedCount'",
                        self::setter('TypedTarget', 'guardedCount', "'abc'", 'TypedTarget::setGuardedCount()', 'array'),
                    ],
                ],
                'staticPropertyUsesSetter' => [
                    [
                        "'shared'",
                        self::setter(
                            'TypedTarget',
                            'shared',
                            "'abc'",
                            'TypedTarget::setShared()',
                            'array',
                        ),
                    ],
                ],
                'staticSetter' => [
                    [
                        "'defaultCount'",
                        self::setter('TypedTarget', 'defaultCount', "'abc'", 'TypedTarget::setDefaultCount()', 'int'),
                    ],
                ],
                'stringForArrayProperty' => [
                    [
                        "'items'",
                        self::property(
                            'TypedTarget',
                            'items',
                            "'abc'",
                            'array',
                        ),
                    ],
                ],
                'stringForArraySetter' => [
                    [
                        "'tags'",
                        self::setter(
                            'TypedTarget',
                            'tags',
                            "'abc'",
                            'TypedTarget::setTags()',
                            'array',
                        ),
                    ],
                ],
                'stringForClosureSetter' => [
                    [
                        "'factory'",
                        self::setter(
                            'TypedTarget',
                            'factory',
                            "'strlen'",
                            'TypedTarget::setFactory()',
                            'Closure',
                        ),
                    ],
                ],
                'stringForEnumProperty' => [
                    [
                        "'suit'",
                        self::property(
                            'TypedTarget',
                            'suit',
                            "'H'",
                            self::TARGETS . 'TypedSuit',
                        ),
                    ],
                ],
                'stringForIntSetter' => [
                    [
                        "'limitTo'",
                        self::setter(
                            'TypedTarget',
                            'limitTo',
                            "'abc'",
                            'TypedTarget::setLimitTo()',
                            'int',
                        ),
                    ],
                ],
                'stringForIterableProperty' => [
                    [
                        "'rows'",
                        self::property(
                            'TypedTarget',
                            'rows',
                            "'abc'",
                            'iterable',
                        ),
                    ],
                ],
                'stringForSetterInheritedByChild' => [
                    [
                        "'traitLevel'",
                        self::setter(
                            'TypedChildTarget',
                            'traitLevel',
                            "'abc'",
                            'TypedTarget::setTraitLevel()',
                            'int',
                        ),
                    ],
                ],
                'stringForTraitSetter' => [
                    [
                        "'traitLevel'",
                        self::setter(
                            'TypedTarget',
                            'traitLevel',
                            "'abc'",
                            'TypedTarget::setTraitLevel()',
                            'int',
                        ),
                    ],
                ],
                'stringForTwoParameterSetter' => [
                    [
                        "'pair'",
                        self::setter(
                            'TypedTarget',
                            'pair',
                            "'abc'",
                            'TypedTarget::setPair()',
                            'int',
                        ),
                    ],
                ],
                'stringForVariadicSetter' => [
                    [
                        "'values'",
                        self::setter(
                            'TypedTarget',
                            'values',
                            "'abc'",
                            'TypedTarget::setValues()',
                            'int',
                        ),
                    ],
                ],
                'stringForWidenedSetterOfChild' => [
                    [
                        "'limitTo'",
                        self::setter(
                            'TypedChildTarget',
                            'limitTo',
                            "'abc'",
                            'TypedChildTarget::setLimitTo()',
                            'array|int',
                        ),
                    ],
                ],
                'twoInvalidOptions' => [
                    [
                        "'count'",
                        self::property(
                            'TypedTarget',
                            'count',
                            "''",
                            'int',
                        ),
                    ],
                    [
                        "'title'",
                        self::property(
                            'TypedTarget',
                            'title',
                            'array{}',
                            'string',
                        ),
                    ],
                ],
            ],
        ];
    }

    /**
     * Returns the expected message for an option that Yii passes to a setter of a fixture class.
     */
    private static function setter(
        string $targetClass,
        string $option,
        string $value,
        string $setter,
        string $expected,
    ): string {
        return self::message('Yii', $targetClass, $option, $value, self::TARGETS . $setter, $expected);
    }
}
