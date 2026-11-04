<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use Error;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use TypeError;
use yii\base\UnknownPropertyException;
use yii\di\NotInstantiableException;
use yii2\extensions\phpstan\tests\data\rule\CreateObjectContainerBinding;
use yii2\extensions\phpstan\tests\support\createobject\{ComponentMembersTarget, FactoryTarget, Target};

use function array_diff;
use function array_keys;
use function implode;
use function sprintf;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\rules\CreateObjectContainerBindingRuntimeTest} test cases.
 */
final class CreateObjectContainerBindingRuntimeProvider
{
    /**
     * Fixture methods that aren't executed, with the reason.
     */
    public const array NOT_EXECUTED = [
        'cycle' => 'Container::get() recurses without end on a definition cycle and the process crashes.',
        'definitionOptionsCycle' => 'Container::get() recurses without end on a definition cycle and the process crashes.',
    ];

    /**
     * Runtime outcome of each executed fixture method: the thrown exception class and the start of its message, with
     * `%s` for the fixture namespace, or the created class and an empty message.
     */
    public const array OUTCOMES = [
        'abstractTarget' => [
            NotInstantiableException::class,
            'Can not instantiate %sAbstractTarget.',
        ],
        'aliasToFactory' => [
            FactoryTarget::class,
            '',
        ],
        'aliasToSingleton' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sSingletonTarget::colour',
        ],
        'arrayClassDefinition' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'arrayLegacyClassDefinition' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'classAlias' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'classAndLegacyClassDefinition' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::__class',
        ],
        'classIdDefinitionOptions' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sDefinitionOptionsTarget::colour',
        ],
        'componentAlias' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sComponentMembersTarget::colour',
        ],
        'componentAliasInvalidValue' => [
            TypeError::class,
            'Cannot assign string to property %sComponentMembersTarget::$count',
        ],
        'componentDefinitionEventOption' => [
            ComponentMembersTarget::class,
            '',
        ],
        'componentDefinitionOptions' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sComponentMembersTarget::colour',
        ],
        'componentDefinitionOptionsInvalidValue' => [
            TypeError::class,
            'Cannot assign string to property %sComponentMembersTarget::$count',
        ],
        'configuredFactory' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'constantConfigurationThroughChain' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'constructorParams' => [
            Target::class,
            '',
        ],
        'customConstructor' => [
            TypeError::class,
            'Cannot assign array to property %sCustomConstructorTarget::$name',
        ],
        'definitionAnnotatedOption' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::documented',
        ],
        'definitionConstructOption' => [
            Target::class,
            '',
        ],
        'definitionIntegerOption' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::0',
        ],
        'definitionOptions' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'definitionOptionsInvalidValue' => [
            TypeError::class,
            'Cannot assign array to property %sTarget::$name',
        ],
        'definitionOptionsOnChain' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'definitionOptionsOnChainInvalidValue' => [
            TypeError::class,
            'Cannot assign array to property %sTarget::$tag',
        ],
        'definitionOptionsToCustomConstructor' => [
            TypeError::class,
            'Cannot assign array to property %sCustomConstructorTarget::$name',
        ],
        'definitionOptionsValid' => [
            Target::class,
            '',
        ],
        'definitionOptionsWithParams' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'definitionOptionsWithParamsInvalidValue' => [
            TypeError::class,
            'Cannot assign array to property %sTarget::$name',
        ],
        'definitionProtectedOption' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::internal',
        ],
        'definitionReadonlyOption' => [
            Error::class,
            'Cannot ',
        ],
        'definitionSetterOption' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'definitionStaticOption' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::shared',
        ],
        'definitionUnknownOption' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'emptyDefinition' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sEmptyDefinitionTarget::colour',
        ],
        'emptyDefinitionInvalidValue' => [
            TypeError::class,
            'Cannot assign array to property %sEmptyDefinitionTarget::$name',
        ],
        'emptyParamsDefinition' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'factory' => [
            Target::class,
            '',
        ],
        'instanceDefinition' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'interfaceAlias' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'interfaceAliasInvalidValue' => [
            TypeError::class,
            'Cannot assign array to property %sTarget::$name',
        ],
        'leadingBackslashTargetSkipsFactory' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sFactoryTarget::colour',
        ],
        'legacyClassDefinitionOptions' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'mappedComponentAlias' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sComponentMappedTarget::colour',
        ],
        'missingTarget' => [
            NotInstantiableException::class,
            'Failed to instantiate component or class "Missing\Target".',
        ],
        'object' => [
            Target::class,
            '',
        ],
        'paramsOnLaterHop' => [
            Target::class,
            '',
        ],
        'replacedBySingleton' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'singleton' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'singletonWithOptions' => [
            TypeError::class,
            'Cannot assign array to property %sTarget::$name',
        ],
        'threeHopChain' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'threeHopChainInvalidValue' => [
            TypeError::class,
            'Cannot assign array to property %sTarget::$name',
        ],
        'validOptionsThroughChain' => [
            Target::class,
            '',
        ],
        'wrappedDefinitionOptions' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sTarget::colour',
        ],
        'zeroDefinition' => [
            NotInstantiableException::class,
            'Failed to instantiate component or class "binding.zero".',
        ],
    ];

    /**
     * Namespace of the configurable target fixtures.
     */
    private const string TARGETS = 'yii2\extensions\phpstan\tests\support\createobject\\';

    /**
     * Returns the runtime outcome of an executed fixture method: the thrown or created class and the start of the
     * message.
     *
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

        $fixture = new ReflectionClass(CreateObjectContainerBinding::class);

        foreach ($fixture->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $methods[] = $method->getName();
        }

        $listed = [
            ...array_keys(self::OUTCOMES),
            ...array_keys(self::NOT_EXECUTED),
        ];
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
