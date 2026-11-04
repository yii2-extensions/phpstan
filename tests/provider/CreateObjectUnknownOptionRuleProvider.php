<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use ReflectionClass;
use RuntimeException;
use yii2\extensions\phpstan\tests\data\rule\{
    CreateObjectComponentOption,
    CreateObjectConstantUnknownOption,
    CreateObjectConstructorArgument,
    CreateObjectContainerBinding,
    CreateObjectStaticTarget,
    CreateObjectUnknownOption,
};
use yii2\extensions\phpstan\tests\support\FixtureMethod;

use function array_diff;
use function array_keys;
use function implode;
use function sprintf;
use function str_contains;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\rules\CreateObjectUnknownOptionRuleTest} test cases.
 */
final class CreateObjectUnknownOptionRuleProvider
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
     * Returns the expected message for an option of a fixture or fully qualified class.
     */
    private static function message(string $targetClass, string $option, string $calledClass = 'Yii'): string
    {
        return sprintf(
            '%s::createObject() sets unknown property %s::$%s: the class has no public property or setter with that '
                . 'name.',
            $calledClass,
            str_contains($targetClass, '\\') ? $targetClass : self::TARGETS . $targetClass,
            $option,
        );
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
                'componentClass' => [
                    [
                        "'colour'",
                        self::message(
                            'yii\base\Component',
                            'colour',
                        ),
                    ],
                ],
                'constantConfiguration' => [
                    [
                        null,
                        self::message(
                            'ComponentTarget',
                            'colour',
                        ),
                    ],
                ],
                'eventBeforeOption' => [
                    [
                        "'colour'",
                        self::message(
                            'ComponentMembersTarget',
                            'colour',
                        ),
                    ],
                ],
                'eventOverrideAfterOption' => [
                    [
                        "'colour'",
                        self::message(
                            'ComponentEventTarget',
                            'colour',
                        ),
                    ],
                ],
                'finalClass' => [
                    [
                        "'colour'",
                        self::message(
                            'ComponentTarget',
                            'colour',
                        ),
                    ],
                ],
                'lastClassValue' => [
                    [
                        "'colour'",
                        self::message(
                            'ComponentTarget',
                            'colour',
                        ),
                    ],
                ],
                'optionBeforeSetterAttachment' => [
                    [
                        "'colour'",
                        self::message(
                            'ComponentMembersTarget',
                            'colour',
                        ),
                    ],
                ],
                'privateProperty' => [
                    [
                        "'secret'",
                        self::message(
                            'ComponentMembersTarget',
                            'secret',
                        ),
                    ],
                ],
                'protectedPropertyWithoutSetter' => [
                    [
                        "'internal'",
                        self::message(
                            'ComponentMembersTarget',
                            'internal',
                        ),
                    ],
                ],
                'twoUnknownOptions' => [
                    [
                        "'colour'",
                        self::message(
                            'ComponentMembersTarget',
                            'colour',
                        ),
                    ],
                    [
                        "'size'",
                        self::message(
                            'ComponentMembersTarget',
                            'size',
                        ),
                    ],
                ],
                'unknownAfterPublicProperty' => [
                    [
                        "'colour'",
                        self::message(
                            'ComponentMembersTarget',
                            'colour',
                        ),
                    ],
                ],
                'unknownOption' => [
                    [
                        "'colour'",
                        self::message(
                            'ComponentMembersTarget',
                            'colour',
                        ),
                    ],
                ],
                'uppercaseBehaviorPrefix' => [
                    [
                        "'As colour'",
                        self::message(
                            'ComponentMembersTarget',
                            'As colour',
                        ),
                    ],
                ],
                'uppercaseEventPrefix' => [
                    [
                        "'On change'",
                        self::message(
                            'ComponentMembersTarget',
                            'On change',
                        ),
                    ],
                ],
            ],
            CreateObjectConstantUnknownOption::class => [
                'assignedOffset' => [
                    [
                        null,
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'classKeyConstant' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'classValueConstant' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'constantConfiguration' => [
                    [
                        null,
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'constantEmptyParams' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'copiedConstant' => [
                    [
                        null,
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'dunderClassConstant' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'leadingBackslashConstantMissesBinding' => [
                    [
                        "'colour'",
                        self::message(
                            'FactoryTarget',
                            'colour',
                        ),
                    ],
                ],
                'literalClassReplacedByUnpackedConstant' => [
                    [
                        null,
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'literalKeyAfterConstantKey' => [
                    [
                        'Options::UNKNOWN',
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'localClassValue' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'localConfiguration' => [
                    [
                        null,
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'localEmptyParams' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'namedConstantConfigurationWithUnpackedParams' => [
                    [
                        null,
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'numericStringKey' => [
                    [
                        "'1'",
                        self::message(
                            'Target',
                            '1',
                        ),
                    ],
                ],
                'otherClassConstantKey' => [
                    [
                        'Options::UNKNOWN',
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'selfConstantKey' => [
                    [
                        'self::KEY',
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'twoUnpackedConstants' => [
                    [
                        null,
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'unionOfOneShape' => [
                    [
                        null,
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'unpackedComponentConfigurationWithReplacedClass' => [
                    [
                        null,
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'unpackedConstantBeforeLiteral' => [
                    [
                        null,
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
            ],
            CreateObjectConstructorArgument::class => [
                'aliasToDefinitionParams' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'aliasToDefinitionParamsReplaced' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'classIdDefinitionParams' => [
                    [
                        "'colour'",
                        self::message(
                            'ArgumentsTarget',
                            'colour',
                        ),
                    ],
                ],
                'classIdDefinitionParamsReplaced' => [
                    [
                        "'colour'",
                        self::message(
                            'ArgumentsTarget',
                            'colour',
                        ),
                    ],
                ],
                'componentParams' => [
                    [
                        "'colour'",
                        self::message(
                            'ComponentMembersTarget',
                            'colour',
                        ),
                    ],
                ],
                'constantParams' => [
                    [
                        null,
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'construct' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'constructEmptyWithOptions' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'constructIgnoredForOptions' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'constructLegacyClass' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'constructNamed' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'definitionConstructReplaced' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'definitionConstructReplacedByParams' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'definitionConstructTwoReplaced' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'definitionConstructWithOptions' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'definitionNamedParamsReplaced' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'definitionParamsReplaced' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'definitionParamsWithOptions' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'namedCallArguments' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'paramsAndConstructNamed' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'paramsBeforeConstruct' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'paramsClassKeys' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'paramsConstantKey' => [
                    [
                        null,
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'paramsConstructKey' => [
                    [
                        "'__construct()'",
                        self::message(
                            'Target',
                            '__construct()',
                        ),
                    ],
                ],
                'paramsDuplicateArgument' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'paramsIgnoredForOptions' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'paramsLegacyClass' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'paramsNamed' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'paramsNamedIgnoredForOptions' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'paramsPositional' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'paramsScalarIgnoredForOptions' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'paramsStringZeroKey' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'paramsUnpackedArgument' => [
                    [
                        null,
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'paramsWithClassConfiguration' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'variableClassName' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
            ],
            CreateObjectContainerBinding::class => [
                'arrayClassDefinition' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'arrayLegacyClassDefinition' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'classAlias' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'classIdDefinitionOptions' => [
                    [
                        "'colour'",
                        self::message(
                            'DefinitionOptionsTarget',
                            'colour',
                        ),
                    ],
                ],
                'componentAlias' => [
                    [
                        "'colour'",
                        self::message(
                            'ComponentMembersTarget',
                            'colour',
                        ),
                    ],
                ],
                'componentDefinitionOptions' => [
                    [
                        "'colour'",
                        self::message(
                            'ComponentMembersTarget',
                            'colour',
                        ),
                    ],
                ],
                'constantConfigurationThroughChain' => [
                    [
                        null,
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'definitionOptions' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'definitionOptionsOnChain' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'definitionOptionsWithParams' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'emptyDefinition' => [
                    [
                        "'colour'",
                        self::message(
                            'EmptyDefinitionTarget',
                            'colour',
                        ),
                    ],
                ],
                'emptyParamsDefinition' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'instanceDefinition' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'interfaceAlias' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'leadingBackslashTargetSkipsFactory' => [
                    [
                        "'colour'",
                        self::message(
                            'FactoryTarget',
                            'colour',
                        ),
                    ],
                ],
                'legacyClassDefinitionOptions' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'threeHopChain' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'wrappedDefinitionOptions' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
            ],
            CreateObjectStaticTarget::class => [
                'createWithConstantKey' => [
                    [
                        'self::KEY',
                        self::message(
                            CreateObjectStaticTarget::class,
                            'colour',
                        ),
                    ],
                ],
            ],
            CreateObjectUnknownOption::class => [
                'aliasedYii' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'annotatedProperties' => [
                    [
                        "'documented'",
                        self::message(
                            'Target',
                            'documented',
                        ),
                    ],
                    [
                        "'annotated'",
                        self::message(
                            'Target',
                            'annotated',
                        ),
                    ],
                ],
                'baseYii' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                            'yii\BaseYii',
                        ),
                    ],
                ],
                'caseInsensitiveMethodName' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'caseSensitivePropertyName' => [
                    [
                        "'Name'",
                        self::message(
                            'Target',
                            'Name',
                        ),
                    ],
                ],
                'dunderClassOverClass' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'duplicateOption' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'emptyParams' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'eventKeyOnBaseObject' => [
                    [
                        "'on change'",
                        self::message(
                            'yii\base\BaseObject',
                            'on change',
                        ),
                    ],
                ],
                'fullyQualifiedYii' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'inheritedNonPublicProperties' => [
                    [
                        "'secret'",
                        self::message(
                            'ChildTarget',
                            'secret',
                        ),
                    ],
                    [
                        "'internal'",
                        self::message(
                            'ChildTarget',
                            'internal',
                        ),
                    ],
                ],
                'lastClassValue' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'leadingBackslash' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'leadingBackslashMissesBinding' => [
                    [
                        "'colour'",
                        self::message(
                            'FactoryTarget',
                            'colour',
                        ),
                    ],
                ],
                'namedArgument' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'namedArgumentsReordered' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'nonPublicProperties' => [
                    [
                        "'internal'",
                        self::message(
                            'Target',
                            'internal',
                        ),
                    ],
                    [
                        "'secret'",
                        self::message(
                            'Target',
                            'secret',
                        ),
                    ],
                ],
                'unknownAfterValidOption' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'unknownOption' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                        ),
                    ],
                ],
                'yiiSubclass' => [
                    [
                        "'colour'",
                        self::message(
                            'Target',
                            'colour',
                            self::TARGETS . 'Factory'
                        ),
                    ],
                ],
            ],
        ];
    }
}
