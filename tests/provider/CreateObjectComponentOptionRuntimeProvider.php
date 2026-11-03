<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use TypeError;
use yii\base\{InvalidCallException, UnknownMethodException, UnknownPropertyException};
use yii\di\NotInstantiableException;
use yii2\extensions\phpstan\tests\data\rule\CreateObjectComponentOption;
use yii2\extensions\phpstan\tests\support\createobject\{
    ComponentBehaviorsTarget,
    ComponentBoundTarget,
    ComponentCustomSetterTarget,
    ComponentEnsureBehaviorsTarget,
    ComponentEventTarget,
    ComponentInheritedBehaviorsTarget,
    ComponentMembersTarget,
};

use function array_diff;
use function array_keys;
use function implode;
use function sprintf;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\rules\CreateObjectComponentOptionRuntimeTest} test cases.
 */
final class CreateObjectComponentOptionRuntimeProvider
{
    /**
     * Runtime outcome of each fixture method: the thrown exception class and the start of its message, with `%s` for
     * the fixture namespace, or the created class and an empty message.
     */
    public const array OUTCOMES = [
        'abstractClass' => [
            NotInstantiableException::class,
            'Can not instantiate %sComponentAbstractTarget.',
        ],
        'acceptedValues' => [
            ComponentMembersTarget::class,
            '',
        ],
        'arrayForStringProperty' => [
            TypeError::class,
            'Cannot assign array to property %sComponentTarget::$name',
        ],
        'behaviorAfterOption' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sComponentMembersTarget::colour',
        ],
        'behaviorBeforeInvalidValue' => [
            TypeError::class,
            'Cannot assign string to property %sComponentMembersTarget::$count',
        ],
        'behaviorBeforeOption' => [
            ComponentMembersTarget::class,
            '',
        ],
        'behaviorsOverride' => [
            ComponentBehaviorsTarget::class,
            '',
        ],
        'behaviorsOverrideInvalidBehaviorValue' => [
            TypeError::class,
            'Cannot assign array to property %sComponentBehavior::$colour',
        ],
        'behaviorsOverrideInvalidValue' => [
            TypeError::class,
            'Cannot assign string to property %sComponentBehaviorsTarget::$count',
        ],
        'boundClass' => [
            ComponentBoundTarget::class,
            '',
        ],
        'componentClass' => [
            UnknownPropertyException::class,
            'Setting unknown property: yii\base\Component::colour',
        ],
        'constantConfiguration' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sComponentTarget::colour',
        ],
        'customConstructor' => [
            TypeError::class,
            'Cannot assign string to property %sComponentCustomConstructorTarget::$count',
        ],
        'customMagicSetter' => [
            ComponentCustomSetterTarget::class,
            '',
        ],
        'ensureBehaviorsOverride' => [
            ComponentEnsureBehaviorsTarget::class,
            '',
        ],
        'event' => [
            ComponentMembersTarget::class,
            '',
        ],
        'eventBeforeOption' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sComponentMembersTarget::colour',
        ],
        'eventOverrideAfterOption' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sComponentEventTarget::colour',
        ],
        'eventOverrideBeforeOption' => [
            ComponentEventTarget::class,
            '',
        ],
        'finalClass' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sComponentTarget::colour',
        ],
        'getterOnly' => [
            InvalidCallException::class,
            'Setting read-only property: %sComponentMembersTarget::title',
        ],
        'inheritedBehaviorsOverride' => [
            ComponentInheritedBehaviorsTarget::class,
            '',
        ],
        'lastClassValue' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sComponentTarget::colour',
        ],
        'mappedAncestor' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sComponentMappedChildTarget::colour',
        ],
        'mappedClass' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sComponentMappedTarget::colour',
        ],
        'mappedInterface' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sComponentContractTarget::colour',
        ],
        'optionBeforeSetterAttachment' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sComponentMembersTarget::colour',
        ],
        'privateProperty' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sComponentMembersTarget::secret',
        ],
        'privateSetterBeforeOption' => [
            UnknownMethodException::class,
            'Calling unknown method: %sComponentMembersTarget::sethidden()',
        ],
        'protectedPropertyUsesSetter' => [
            TypeError::class,
            '%sComponentMembersTarget::setGuarded(): Argument #1',
        ],
        'protectedPropertyWithoutSetter' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sComponentMembersTarget::internal',
        ],
        'setterAttachmentBeforeOption' => [
            ComponentMembersTarget::class,
            '',
        ],
        'setterBeforeOption' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sComponentMembersTarget::colour',
        ],
        'staticProperty' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sComponentMembersTarget::shared',
        ],
        'stringForIntProperty' => [
            TypeError::class,
            'Cannot assign string to property %sComponentMembersTarget::$count',
        ],
        'stringForIntSetter' => [
            TypeError::class,
            '%sComponentMembersTarget::setLimit(): Argument #1',
        ],
        'twoUnknownOptions' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sComponentMembersTarget::colour',
        ],
        'unknownAfterPublicProperty' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sComponentMembersTarget::colour',
        ],
        'unknownOption' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sComponentMembersTarget::colour',
        ],
        'uppercaseBehaviorPrefix' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sComponentMembersTarget::As colour',
        ],
        'uppercaseEventPrefix' => [
            UnknownPropertyException::class,
            'Setting unknown property: %sComponentMembersTarget::On change',
        ],
    ];

    /**
     * Namespace of the configurable target fixtures.
     */
    private const string TARGETS = 'yii2\extensions\phpstan\tests\support\createobject\\';

    /**
     * Returns the name of the fixture method whose body contains the given line.
     */
    public static function methodAt(int|null $line): string
    {
        foreach ((new ReflectionClass(CreateObjectComponentOption::class))->getMethods() as $method) {
            if ($line >= $method->getStartLine() && $line <= $method->getEndLine()) {
                return $method->getName();
            }
        }

        throw new RuntimeException(
            sprintf('No fixture method contains line %s.', $line ?? 'null'),
        );
    }

    /**
     * Returns the runtime outcome of a fixture method: the thrown or created class and the start of the message.
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

        $fixture = new ReflectionClass(CreateObjectComponentOption::class);

        foreach ($fixture->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $methods[] = $method->getName();
        }

        $mismatched = [
            ...array_diff($methods, array_keys(self::OUTCOMES)),
            ...array_diff(array_keys(self::OUTCOMES), $methods),
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
