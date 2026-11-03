<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use TypeError;
use yii\base\{InvalidConfigException, UnknownPropertyException};
use yii2\extensions\phpstan\tests\data\rule\CreateObjectConstructorArgument;
use yii2\extensions\phpstan\tests\support\createobject\{ComponentMembersTarget, Target, TypedTarget};

use function array_diff;
use function array_keys;
use function implode;
use function sprintf;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\rules\CreateObjectConstructorArgumentRuntimeTest} test
 * cases.
 */
final class CreateObjectConstructorArgumentRuntimeProvider
{
    /**
     * Runtime outcome of each fixture method: the thrown exception class and the start of its message, with `%1$s` for
     * the fixture namespace, or the created class and an empty message.
     */
    public const array OUTCOMES = [
        'aliasToDefinitionParams' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'aliasToDefinitionParamsReplaced' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'classIdDefinitionParams' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sArgumentsTarget::colour',
        ],
        'classIdDefinitionParamsReplaced' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sArgumentsTarget::colour',
        ],
        'componentBehaviorInArgument' => [
            ComponentMembersTarget::class,
            '',
        ],
        'componentParams' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sComponentMembersTarget::colour',
        ],
        'componentParamsInvalidValue' => [
            TypeError::class,
            'Cannot assign string to property %sComponentMembersTarget::$count of type int',
        ],
        'constantParams' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'construct' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'constructAndParamsMixed' => [
            InvalidConfigException::class,
            'Dependencies indexed by name and by position in the same array are not allowed.',
        ],
        'constructEmptyWithOptions' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'constructIgnoredForOptions' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'constructInvalidValue' => [
            TypeError::class,
            'Cannot assign string to property %sTypedTarget::$count of type int',
        ],
        'constructLegacyClass' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'constructNamed' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'constructNull' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::__construct()',
        ],
        'constructTwoArguments' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'constructValid' => [
            Target::class,
            '',
        ],
        'customConstructorParams' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sCustomConstructorTarget::colour',
        ],
        'definitionConstructNull' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::__construct()',
        ],
        'definitionConstructReplaced' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'definitionConstructReplacedByParams' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'definitionConstructSuppliesConfiguration' => [
            Target::class,
            '',
        ],
        'definitionConstructTwoReplaced' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'definitionConstructTwoWithOptions' => [
            Target::class,
            '',
        ],
        'definitionConstructWithOptions' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'definitionCustomConstructor' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sCustomConstructorTarget::colour',
        ],
        'definitionHopNamedParams' => [
            InvalidConfigException::class,
            'Dependencies indexed by name and by position in the same array are not allowed.',
        ],
        'definitionHopSecondParam' => [
            Target::class,
            '',
        ],
        'definitionNamedParamsMixed' => [
            InvalidConfigException::class,
            'Dependencies indexed by name and by position in the same array are not allowed.',
        ],
        'definitionNamedParamsReplaced' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'definitionOptionsDropParams' => [
            Target::class,
            '',
        ],
        'definitionParamsBeforeConstruct' => [
            Target::class,
            '',
        ],
        'definitionParamsInvalidValue' => [
            TypeError::class,
            'Cannot assign string to property %sTypedTarget::$count of type int',
        ],
        'definitionParamsReplaced' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'definitionParamsTwoWithOptions' => [
            Target::class,
            '',
        ],
        'definitionParamsWithOptions' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'definitionParamsWithOptionsInvalidValue' => [
            TypeError::class,
            'Cannot assign string to property %sTypedTarget::$count of type int',
        ],
        'definitionSingletonParams' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'dynamicParams' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'namedCallArguments' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'optionsInstanceInvalidValue' => [
            TypeError::class,
            'Cannot assign %1$sTypedValue to property %1$sTypedTarget::$items of type array',
        ],
        'optionsInstanceNullableValue' => [
            TypeError::class,
            'Cannot assign %1$sTypedValue to property %1$sArgumentsTarget::$list of type ?array',
        ],
        'optionsInstanceValue' => [
            TypedTarget::class,
            '',
        ],
        'paramsAndConstructNamed' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'paramsBeforeConstruct' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'paramsClassKeys' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::class',
        ],
        'paramsConstructKey' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::__construct()',
        ],
        'paramsDuplicateArgument' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'paramsIgnoredForOptions' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'paramsIgnoredForOptionsInvalidValue' => [
            TypeError::class,
            'Cannot assign string to property %sTypedTarget::$count of type int',
        ],
        'paramsInstanceValue' => [
            TypeError::class,
            'Cannot assign yii\di\Instance to property %1$sTypedTarget::$value of type ?%1$sTypedValue',
        ],
        'paramsIntegerOptionKey' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::0',
        ],
        'paramsInvalidValue' => [
            TypeError::class,
            'Cannot assign string to property %sTypedTarget::$count of type int',
        ],
        'paramsLegacyClass' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'paramsMixedKeys' => [
            InvalidConfigException::class,
            'Dependencies indexed by name and by position in the same array are not allowed.',
        ],
        'paramsNamed' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'paramsNamedIgnoredForOptions' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'paramsNull' => [
            Target::class,
            '',
        ],
        'paramsOtherName' => [
            Target::class,
            '',
        ],
        'paramsPositional' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'paramsScalarIgnoredForOptions' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'paramsSecondPosition' => [
            Target::class,
            '',
        ],
        'paramsStringZeroKey' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'paramsTwoArguments' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'paramsTwoArgumentsDropOptions' => [
            Target::class,
            '',
        ],
        'paramsUnpackedArguments' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'paramsValid' => [
            Target::class,
            '',
        ],
        'paramsWithClassConfiguration' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'paramsWithUnionKeys' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'variableClassName' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
    ];

    /**
     * Namespace of the configurable target fixtures.
     */
    private const string TARGETS = 'yii2\extensions\phpstan\tests\support\createobject\\';

    /**
     * @phpstan-return array{class-string, string}
     */
    public static function outcome(string $method): array
    {
        [$class, $message] = self::OUTCOMES[$method] ?? throw new RuntimeException(
            sprintf('Method "%s" of the fixture has no runtime outcome.', $method),
        );

        return [
            $class,
            sprintf($message, self::TARGETS),
        ];
    }

    /**
     * @phpstan-return iterable<string, array{string, class-string, string}>
     */
    public static function outcomeProvider(): iterable
    {
        $methods = [];

        $fixture = new ReflectionClass(CreateObjectConstructorArgument::class);

        foreach ($fixture->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $methods[] = $method->getName();
        }

        $listed = array_keys(self::OUTCOMES);
        $mismatched = [
            ...array_diff($methods, $listed),
            ...array_diff($listed, $methods),
        ];

        if ($mismatched !== []) {
            throw new RuntimeException(
                sprintf('Outcomes must list exactly the fixture methods: %s.', implode(', ', $mismatched)),
            );
        }

        foreach (array_keys(self::OUTCOMES) as $method) {
            yield $method => [$method, ...self::outcome($method)];
        }
    }
}
