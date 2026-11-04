<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use Error;
use RuntimeException;
use TypeError;
use yii\base\{InvalidCallException, UnknownMethodException, UnknownPropertyException};
use yii\di\Instance;
use yii2\extensions\phpstan\tests\data\rule\{
    NewObjectInvalidOptionType,
    NewObjectLateStaticReference,
    NewObjectSelfReference,
    NewObjectStandAside,
    NewObjectUnknownOption,
    NewObjectValidOption,
};
use yii2\extensions\phpstan\tests\support\createobject\{
    AliasTarget,
    ChildTarget,
    ComponentBehaviorsTarget,
    ComponentCustomConstructorTarget,
    ComponentMappedTarget,
    ComponentMembersTarget,
    ComponentTarget,
    CustomConstructorTarget,
    Target,
    TypedPlain,
    TypedTarget,
};

use function sprintf;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\rules\NewObjectRuntimeTest} test cases.
 */
final class NewObjectRuntimeProvider
{
    /**
     * Outcome of each fixture method, by fixture class and method name.
     *
     * @phpstan-var array<class-string, array<string, array{class-string, string|null}>>
     */
    private const array OUTCOMES = [
        NewObjectInvalidOptionType::class => [
            'arrayForStringSetter' => [
                TypeError::class,
                TypedTarget::class . '::setText(): Argument #1',
            ],
            'classKeyForIntSetter' => [
                TypeError::class,
                TypedTarget::class . '::setClass(): Argument #1',
            ],
            'componentProperty' => [
                TypeError::class,
                'Cannot assign string to property ' . ComponentMembersTarget::class . '::$count',
            ],
            'constantConfiguration' => [
                TypeError::class,
                'Cannot assign string to property ' . TypedTarget::class . '::$count',
            ],
            'inheritedProperty' => [
                TypeError::class,
                'Cannot assign string to property ' . TypedTarget::class . '::$count',
            ],
            'instanceForClassProperty' => [
                TypeError::class,
                'Cannot assign ' . Instance::class . ' to property ' . TypedTarget::class . '::$value',
            ],
            'namedConfig' => [
                TypeError::class,
                'Cannot assign array to property ' . TypedTarget::class . '::$count',
            ],
            'nonNumericStringForIntProperty' => [
                TypeError::class,
                'Cannot assign string to property ' . TypedTarget::class . '::$count',
            ],
            'nullForStringProperty' => [
                TypeError::class,
                'Cannot assign null to property ' . TypedTarget::class . '::$title',
            ],
            'objectForIntProperty' => [
                TypeError::class,
                'Cannot assign ' . TypedPlain::class . ' to property ' . TypedTarget::class . '::$count',
            ],
        ],
        NewObjectLateStaticReference::class => [
            'staticClass' => [UnknownPropertyException::class, self::UNKNOWN . NewObjectLateStaticReference::class . '::colour'],
        ],
        NewObjectSelfReference::class => [
            'closureSelf' => [UnknownPropertyException::class, self::UNKNOWN . NewObjectSelfReference::class . '::colour'],
            'parentClass' => [UnknownPropertyException::class, self::UNKNOWN . Target::class . '::shade'],
            'selfClass' => [UnknownPropertyException::class, self::UNKNOWN . NewObjectSelfReference::class . '::colour'],
            'selfValid' => [NewObjectSelfReference::class, null],
            'staticClass' => [UnknownPropertyException::class, self::UNKNOWN . NewObjectSelfReference::class . '::colour'],
        ],
        NewObjectStandAside::class => [
            'anonymousClass' => [
                UnknownPropertyException::class,
                self::UNKNOWN . Target::class . '@anonymous',
            ],
            'behaviorsOverrideReceivesOption' => [
                ComponentBehaviorsTarget::class,
                null,
            ],
            'behaviorsOverrideUnknownOption' => [
                UnknownPropertyException::class,
                self::UNKNOWN . ComponentBehaviorsTarget::class . '::shade',
            ],
            'componentCustomConstructor' => [
                UnknownPropertyException::class,
                self::UNKNOWN . ComponentCustomConstructorTarget::class . '::colour',
            ],
            'componentMappedBehavior' => [
                UnknownPropertyException::class,
                self::UNKNOWN . ComponentMappedTarget::class . '::shade',
            ],
            'componentSetterBeforeOption' => [
                ComponentMembersTarget::class,
                null,
            ],
            'customConstructorConsumesOption' => [
                CustomConstructorTarget::class,
                null,
            ],
            'customConstructorUnknownOption' => [
                UnknownPropertyException::class,
                self::UNKNOWN . CustomConstructorTarget::class . '::colour',
            ],
            'documentedConfiguration' => [
                UnknownPropertyException::class,
                self::UNKNOWN . Target::class . '::colour',
            ],
            'dynamicClass' => [
                UnknownPropertyException::class,
                self::UNKNOWN . Target::class . '::colour',
            ],
            'expressionClass' => [
                UnknownPropertyException::class,
                self::UNKNOWN . Target::class . '::colour',
            ],
            'extraArgument' => [
                UnknownPropertyException::class,
                self::UNKNOWN . Target::class . '::colour',
            ],
            'getterOnly' => [
                InvalidCallException::class,
                'Setting read-only property: ' . Target::class . '::count',
            ],
            'instanceForStringProperty' => [
                TypeError::class,
                'Cannot assign ' . Instance::class . ' to property ' . TypedTarget::class . '::$title',
            ],
            'privateSetter' => [
                UnknownMethodException::class,
                'Calling unknown method: ' . Target::class . '::sethidden()',
            ],
            'selfInAnonymousClass' => [
                UnknownPropertyException::class,
                self::UNKNOWN . Target::class . '@anonymous',
            ],
            'unionOfShapes' => [
                UnknownPropertyException::class,
                self::UNKNOWN . Target::class . '::colour',
            ],
            'unknownNamedArgument' => [
                Error::class,
                'Unknown named parameter $options',
            ],
            'unpackedArgument' => [
                UnknownPropertyException::class,
                self::UNKNOWN . Target::class . '::colour',
            ],
            'unpackedNamedArgument' => [
                UnknownPropertyException::class,
                self::UNKNOWN . Target::class . '::colour',
            ],
            'variableKey' => [
                UnknownPropertyException::class,
                self::UNKNOWN . Target::class . '::colour',
            ],
        ],
        NewObjectUnknownOption::class => [
            'aliasedClass' => [
                UnknownPropertyException::class,
                self::UNKNOWN . Target::class . '::colour',
            ],
            'annotatedProperty' => [
                UnknownPropertyException::class,
                self::UNKNOWN . Target::class . '::documented',
            ],
            'boundClass' => [
                UnknownPropertyException::class,
                self::UNKNOWN . AliasTarget::class . '::label',
            ],
            'caseMismatchedProperty' => [
                UnknownPropertyException::class,
                self::UNKNOWN . Target::class . '::Name',
            ],
            'classKey' => [
                UnknownPropertyException::class,
                self::UNKNOWN . Target::class . '::class',
            ],
            'componentClassKey' => [
                UnknownPropertyException::class,
                self::UNKNOWN . ComponentTarget::class . '::class',
            ],
            'componentUnknownOption' => [
                UnknownPropertyException::class,
                self::UNKNOWN . ComponentMembersTarget::class . '::colour',
            ],
            'constantConfiguration' => [
                UnknownPropertyException::class,
                self::UNKNOWN . Target::class . '::colour',
            ],
            'constantKey' => [
                UnknownPropertyException::class,
                self::UNKNOWN . Target::class . '::colour',
            ],
            'constructKey' => [
                UnknownPropertyException::class,
                self::UNKNOWN . Target::class . '::__construct()',
            ],
            'inheritedClass' => [
                UnknownPropertyException::class,
                self::UNKNOWN . ChildTarget::class . '::colour',
            ],
            'legacyClassKey' => [
                UnknownPropertyException::class,
                self::UNKNOWN . Target::class . '::__class',
            ],
            'localVariable' => [
                UnknownPropertyException::class,
                self::UNKNOWN . Target::class . '::colour',
            ],
            'multilineKey' => [
                UnknownPropertyException::class,
                self::UNKNOWN . Target::class . '::colour',
            ],
            'namedConfig' => [
                UnknownPropertyException::class,
                self::UNKNOWN . Target::class . '::colour',
            ],
            'privateProperty' => [
                UnknownPropertyException::class,
                self::UNKNOWN . Target::class . '::secret',
            ],
            'protectedProperty' => [
                UnknownPropertyException::class,
                self::UNKNOWN . Target::class . '::internal',
            ],
            'unknownOption' => [
                UnknownPropertyException::class,
                self::UNKNOWN . Target::class . '::colour',
            ],
        ],
        NewObjectValidOption::class => [
            'boundClass' => [
                AliasTarget::class,
                null,
            ],
            'classKeyThroughSetter' => [
                TypedTarget::class,
                null,
            ],
            'componentEventAndBehavior' => [
                ComponentTarget::class,
                null,
            ],
            'constantConfiguration' => [
                TypedTarget::class,
                null,
            ],
            'emptyConfiguration' => [
                Target::class,
                null,
            ],
            'inheritedMembers' => [
                ChildTarget::class,
                null,
            ],
            'instanceForMixedProperty' => [
                TypedTarget::class,
                null,
            ],
            'instanceForMixedSetter' => [
                TypedTarget::class,
                null,
            ],
            'namedConfig' => [
                Target::class,
                null,
            ],
            'noArgument' => [
                Target::class,
                null,
            ],
            'propertiesAndSetters' => [
                Target::class,
                null,
            ],
            'weakCoercionForProperties' => [
                TypedTarget::class,
                null,
            ],
            'weakCoercionForSetters' => [
                TypedTarget::class,
                null,
            ],
        ],
    ];
    /**
     * Start of the message of `yii\base\UnknownPropertyException` thrown by `__set()`.
     */
    private const string UNKNOWN = 'Setting unknown property: ';

    /**
     * Returns the fixture classes whose methods are recorded.
     *
     * @phpstan-return list<class-string>
     */
    public static function fixtures(): array
    {
        return [
            NewObjectInvalidOptionType::class,
            NewObjectLateStaticReference::class,
            NewObjectSelfReference::class,
            NewObjectStandAside::class,
            NewObjectUnknownOption::class,
            NewObjectValidOption::class,
        ];
    }

    /**
     * @phpstan-return iterable<string, array{class-string, string, class-string, string|null}>
     */
    public static function methodProvider(): iterable
    {
        foreach (self::OUTCOMES as $fixture => $methods) {
            foreach ($methods as $method => [$outcome, $message]) {
                yield "{$fixture}::{$method}" => [$fixture, $method, $outcome, $message];
            }
        }
    }

    /**
     * Returns the method names recorded for a fixture, in declaration order of the record.
     *
     * @phpstan-param class-string $fixture
     *
     * @phpstan-return list<string>
     */
    public static function methods(string $fixture): array
    {
        $methods = [];

        foreach (self::OUTCOMES[$fixture] ?? [] as $method => $outcome) {
            $methods[] = $method;
        }

        return $methods;
    }

    /**
     * Returns the recorded outcome of a fixture method.
     *
     * @phpstan-param class-string $fixture
     *
     * @phpstan-return array{class-string, string|null}
     */
    public static function outcome(string $fixture, string $method): array
    {
        return self::OUTCOMES[$fixture][$method] ?? throw new RuntimeException(
            sprintf('No outcome is recorded for "%s::%s()".', $fixture, $method),
        );
    }
}
