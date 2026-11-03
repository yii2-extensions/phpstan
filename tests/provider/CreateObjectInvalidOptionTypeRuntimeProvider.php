<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use Error;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use TypeError;
use yii\base\{UnknownMethodException, UnknownPropertyException};
use yii2\extensions\phpstan\tests\data\rule\{
    CreateObjectConstantInvalidOptionType,
    CreateObjectInvalidOptionType,
    CreateObjectOptionTypeStandAside,
    CreateObjectValidOptionType,
};
use yii2\extensions\phpstan\tests\support\createobject\{
    FactoryTarget,
    InstanceTarget,
    TypedChildTarget,
    TypedTarget,
    UnresolvedTarget,
};

use function array_diff;
use function array_keys;
use function implode;
use function sprintf;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\rules\CreateObjectInvalidOptionTypeRuntimeTest} test cases.
 */
final class CreateObjectInvalidOptionTypeRuntimeProvider
{
    /**
     * Start of the `TypeError` message for each reported call, naming the property or setter that rejects the value.
     */
    private const array REPORTED = [
        CreateObjectConstantInvalidOptionType::class => [
            'assignedOffset' => 'Cannot assign array to property %sTypedTarget::$count',
            'constantConfiguration' => 'Cannot assign string to property %sTypedTarget::$count',
            'localValue' => 'Cannot assign string to property %sTypedTarget::$count',
            'unpackedConstantThenLiteral' => 'Cannot assign string to property %sTypedTarget::$count',
            'variableConfiguration' => '%sTypedTarget::setTags(): Argument #1',
        ],
        CreateObjectInvalidOptionType::class => [
            'arrayForBoolProperty' => 'Cannot assign array to property %sTypedTarget::$enabled',
            'arrayForIntOrStringProperty' => 'Cannot assign array to property %sTypedTarget::$key',
            'arrayForIntProperty' => 'Cannot assign array to property %sTypedTarget::$count',
            'arrayForNullableStringSetter' => '%sTypedTarget::setLabel(): Argument #1',
            'arrayForPublicPropertyBeforeSetter' => 'Cannot assign array to property %sTypedTarget::$both',
            'arrayForStringProperty' => 'Cannot assign array to property %sTypedTarget::$title',
            'arrayForStringSetter' => '%sTypedTarget::setText(): Argument #1',
            'constantKey' => 'Cannot assign string to property %sTypedTarget::$count',
            'enumCaseForIntProperty' => 'Cannot assign %sTypedSuit to property %sTypedTarget::$count',
            'enumConstantForIntProperty' => 'Cannot assign %sTypedSuit to property %sTypedTarget::$count',
            'finalClassForInterfaceProperty' => 'Cannot assign %sTypedValue to property %sTypedTarget::$contract',
            'inheritedProperty' => 'Cannot assign string to property %sTypedTarget::$count',
            'lastDuplicateValue' => 'Cannot assign string to property %sTypedTarget::$count',
            'leadingNumericStringForFloatProperty' => 'Cannot assign string to property %sTypedTarget::$ratio',
            'multilineKey' => 'Cannot assign string to property %sTypedTarget::$count',
            'namedArguments' => 'Cannot assign string to property %sTypedTarget::$count',
            'nonNumericStringForIntProperty' => 'Cannot assign string to property %sTypedTarget::$count',
            'nonNumericStringForNullableIntProperty' => 'Cannot assign string to property %sTypedTarget::$limit',
            'nullForIntProperty' => 'Cannot assign null to property %sTypedTarget::$count',
            'nullForStringProperty' => 'Cannot assign null to property %sTypedTarget::$title',
            'objectForBoolSetter' => '%sTypedTarget::setFlag(): Argument #1',
            'objectForFinalClassProperty' => 'Cannot assign stdClass to property %sTypedTarget::$value',
            'objectForIntProperty' => 'Cannot assign %sTypedPlain to property %sTypedTarget::$count',
            'ownPropertyOfChild' => 'Cannot assign string to property %sTypedChildTarget::$weight',
            'protectedPropertyUsesSetter' => '%sTypedTarget::setGuardedCount(): Argument #1',
            'staticPropertyUsesSetter' => '%sTypedTarget::setShared(): Argument #1',
            'staticSetter' => '%sTypedTarget::setDefaultCount(): Argument #1',
            'stringForArrayProperty' => 'Cannot assign string to property %sTypedTarget::$items',
            'stringForArraySetter' => '%sTypedTarget::setTags(): Argument #1',
            'stringForClosureSetter' => '%sTypedTarget::setFactory(): Argument #1',
            'stringForEnumProperty' => 'Cannot assign string to property %sTypedTarget::$suit',
            'stringForIntSetter' => '%sTypedTarget::setLimitTo(): Argument #1',
            'stringForIterableProperty' => 'Cannot assign string to property %sTypedTarget::$rows',
            'stringForSetterInheritedByChild' => '%sTypedTarget::setTraitLevel(): Argument #1',
            'stringForTraitSetter' => '%sTypedTarget::setTraitLevel(): Argument #1',
            'stringForTwoParameterSetter' => '%sTypedTarget::setPair(): Argument #1',
            'stringForVariadicSetter' => '%sTypedTarget::setValues(): Argument #1',
            'stringForWidenedSetterOfChild' => '%sTypedChildTarget::setLimitTo(): Argument #1',
            'twoInvalidOptions' => 'Cannot assign string to property %sTypedTarget::$count',
        ],
    ];

    /**
     * Runtime outcome of each call the rule leaves alone: the thrown exception class or the created class.
     */
    private const array STAND_ASIDE = [
        'aliasBinding' => TypeError::class,
        'arrayForCallableSetter' => TypeError::class,
        'constructorArguments' => TypedTarget::class,
        'customConstructor' => TypeError::class,
        'customMagicSetter' => TypeError::class,
        'declaredObjectForStringSetter' => TypedTarget::class,
        'dynamicKey' => UnknownPropertyException::class,
        'emptyDefinitionBinding' => TypeError::class,
        'emptyOptionName' => TypeError::class,
        'enumCaseForStringSetter' => TypeError::class,
        'factoryBinding' => FactoryTarget::class,
        'infinityForIntProperty' => TypeError::class,
        'instanceBinding' => InstanceTarget::class,
        'intForFalseSetter' => TypeError::class,
        'nonEmptyParams' => TypedTarget::class,
        'nonFinalObjectForStringSetter' => TypeError::class,
        'partiallyCompatibleUnion' => TypeError::class,
        'phpDocOnlyConfiguration' => TypeError::class,
        'phpDocOnlyValue' => TypeError::class,
        'privateSetter' => UnknownMethodException::class,
        'readonlyProperty' => Error::class,
        'singletonBinding' => TypeError::class,
        'stringValueOfUnknownContent' => TypeError::class,
        'unknownFunctionForCallableSetter' => TypeError::class,
        'unknownOption' => UnknownPropertyException::class,
        'unresolvedBinding' => UnresolvedTarget::class,
    ];

    /**
     * Namespace of the configurable target fixtures.
     */
    private const string TARGETS = 'yii2\extensions\phpstan\tests\support\createobject\\';

    /**
     * Created class of each call whose options are all assigned.
     */
    private const array VALID = [
        'arrayForWidenedSetterOfChild' => TypedChildTarget::class,
    ];

    /**
     * @phpstan-return iterable<string, array{class-string, string, string}>
     */
    public static function reportedProvider(): iterable
    {
        foreach (self::REPORTED as $fixture => $messages) {
            self::assertMethods($fixture, array_keys($messages));

            foreach ($messages as $method => $message) {
                yield "{$fixture}::{$method}" => [
                    $fixture,
                    $method,
                    sprintf($message, self::TARGETS, self::TARGETS),
                ];
            }
        }
    }

    /**
     * @phpstan-return iterable<string, array{class-string, string, class-string}>
     */
    public static function standAsideProvider(): iterable
    {
        self::assertMethods(CreateObjectOptionTypeStandAside::class, array_keys(self::STAND_ASIDE));

        foreach (self::STAND_ASIDE as $method => $outcome) {
            yield $method => [CreateObjectOptionTypeStandAside::class, $method, $outcome];
        }
    }

    /**
     * @phpstan-return iterable<string, array{class-string, string, class-string}>
     */
    public static function validProvider(): iterable
    {
        foreach (
            (new ReflectionClass(CreateObjectValidOptionType::class))
                ->getMethods(ReflectionMethod::IS_PUBLIC) as $method
        ) {
            yield $method->getName() => [
                CreateObjectValidOptionType::class,
                $method->getName(),
                self::VALID[$method->getName()] ?? TypedTarget::class,
            ];
        }
    }

    /**
     * Checks that the expectations of a fixture name exactly its public methods.
     *
     * @phpstan-param class-string $fixture
     * @phpstan-param list<string> $expected
     */
    private static function assertMethods(string $fixture, array $expected): void
    {
        $methods = [];

        foreach ((new ReflectionClass($fixture))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $methods[] = $method->getName();
        }

        $missing = [
            ...array_diff($methods, $expected),
            ...array_diff($expected, $methods),
        ];

        if ($missing !== []) {
            throw new RuntimeException(
                sprintf('Expectations of "%s" must list exactly its methods: %s.', $fixture, implode(', ', $missing)),
            );
        }
    }
}
