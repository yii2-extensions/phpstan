<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use yii\base\{Behavior, Component};
use yii2\extensions\phpstan\tests\data\rule\behavior\{
    ConstantOptionBehaviorsOwner,
    ReportedOptionBehaviorsOwner,
    StandAsideOptionBehaviorsOwner,
    ValidOptionBehaviorsOwner,
};
use yii2\extensions\phpstan\tests\support\behavior\{
    AttachableBehavior,
    BehaviorOwner,
    ConstructorBehavior,
    NotAttachableObject,
    OptionBehavior,
    OptionDefaultsBehavior,
    OptionTargetBehavior,
    SetOverrideBehavior,
};

use function strrpos;
use function substr;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\rules\BehaviorUnknownOptionRuntimeTest} test cases.
 */
final class BehaviorUnknownOptionRuntimeProvider
{
    /**
     * Outcome of `Component::__set()` rejecting the supplied class of an `as` option.
     */
    private const string NOT_BEHAVIOR = 'yii\base\InvalidConfigException: Class is not of type yii\base\Behavior or '
        . 'its subclasses';

    /**
     * @phpstan-return iterable<string, array{string, string, string}>
     */
    public static function asCaseProvider(): iterable
    {
        yield from self::cases('as');
    }

    /**
     * @phpstan-return iterable<string, array{string, string, string}>
     */
    public static function attachCaseProvider(): iterable
    {
        yield from self::cases('attach');
    }

    /**
     * Recorded outcomes of the `OptionAttachCalls` and `AsBehaviorOptions` cases by fixture, method and case.
     *
     * @phpstan-return array<string, array<string, array<string, string>>>
     */
    public static function callOutcomes(): array
    {
        return [
            'attach' => [
                'reported' => [
                    'unknown' => self::unknown(OptionBehavior::class, 'missing'),
                    'named' => self::unknown(OptionTargetBehavior::class, 'missing'),
                    'onComponent' => self::unknown(OptionBehavior::class, 'hidden'),
                    'constantArgument' => self::unknown(OptionBehavior::class, 'missing'),
                    'listed' => self::unknown(OptionBehavior::class, 'missing'),
                    'anonymous' => self::unknown(OptionBehavior::class, 'missing'),
                    'constantList' => self::unknown(OptionBehavior::class, 'missing'),
                ],
                'standAside' => [
                    'overridden' => 'none',
                    'dynamicConfiguration' => self::unknown(OptionBehavior::class, 'missing'),
                    'dynamicKey' => self::unknown(OptionBehavior::class, 'missing'),
                    'mappedAway' => self::unknown(NotAttachableObject::class, 'missing'),
                ],
                'valid' => [
                    'property' => OptionBehavior::class,
                    'setter' => OptionBehavior::class,
                    'redirected' => OptionTargetBehavior::class,
                ],
            ],
            'as' => [
                'reported' => [
                    'unknown' => self::unknown(OptionBehavior::class, 'missing'),
                    'dunderWins' => self::unknown(OptionBehavior::class, 'onlyOnTarget'),
                    'dunderAccepted' => self::unknown(OptionBehavior::class, 'missing'),
                    'redirected' => self::unknown(OptionTargetBehavior::class, 'missing'),
                    'leadingSlash' => self::unknown(OptionBehavior::class, 'missing'),
                    'attachOverride' => self::unknown(OptionBehavior::class, 'missing'),
                    'params' => self::unknown(OptionBehavior::class, 'missing'),
                    'constant' => self::unknown(OptionBehavior::class, 'missing'),
                ],
                'standAside' => [
                    'behaviorItself' => self::NOT_BEHAVIOR,
                    'containerId' => self::NOT_BEHAVIOR,
                    'mappedAway' => self::unknown(NotAttachableObject::class, 'missing'),
                    'nullDunder' => self::unknown(OptionBehavior::class, '__class'),
                    'union' => OptionBehavior::class,
                    'getterOnly' => 'yii\base\InvalidCallException: Setting read-only property: '
                        . OptionBehavior::class . '::readOnly',
                    'setOverride' => 'none',
                    'baseObjectOwner' => self::unknown(NotAttachableObject::class, 'as baseObjectOwner'),
                    'notAsKey' => 'yii\base\UnknownPropertyException: Setting unknown property: '
                        . BehaviorOwner::class . '::As notAsKey',
                ],
                'valid' => [
                    'property' => OptionBehavior::class,
                    'setter' => OptionBehavior::class,
                    'redirected' => OptionTargetBehavior::class,
                ],
            ],
        ];
    }

    /**
     * Recorded outcomes of the `behaviors()` entries by fixture owner and behavior name.
     *
     * @phpstan-return array<class-string<Component>, array<int|string, string>>
     */
    public static function entryOutcomes(): array
    {
        return [
            ReportedOptionBehaviorsOwner::class => [
                'unknown' => self::unknown(OptionBehavior::class, 'missing'),
                'protected' => self::unknown(OptionBehavior::class, 'hidden'),
                'caseMismatch' => self::unknown(OptionBehavior::class, 'Label'),
                'asKey' => self::unknown(OptionBehavior::class, 'as nested'),
                'onKey' => self::unknown(OptionBehavior::class, 'on event'),
                'base' => self::unknown(Behavior::class, 'missing'),
                'afterValid' => self::unknown(OptionBehavior::class, 'missing'),
                'dunderWins' => self::unknown(OptionBehavior::class, 'onlyOnTarget'),
                'containerId' => self::unknown(OptionBehavior::class, 'missing'),
                'aliasChain' => self::unknown(AttachableBehavior::class, 'missing'),
                'redirected' => self::unknown(OptionTargetBehavior::class, 'missing'),
                'definitionOptions' => self::unknown(OptionDefaultsBehavior::class, 'missing'),
                'leadingSlash' => self::unknown(OptionBehavior::class, 'missing'),
                'constructorArgument' => self::unknown(OptionBehavior::class, 'missing'),
                0 => self::unknown(OptionBehavior::class, 'missing'),
            ],
            ConstantOptionBehaviorsOwner::class => [
                'constant' => self::unknown(OptionBehavior::class, 'missing'),
            ],
            ValidOptionBehaviorsOwner::class => [
                'property' => OptionBehavior::class,
                'setter' => OptionBehavior::class,
                'protectedSetter' => OptionBehavior::class,
                'inheritedProperty' => OptionBehavior::class,
                'inheritedSetter' => OptionBehavior::class,
                'owner' => OptionBehavior::class,
                'dunderWins' => OptionTargetBehavior::class,
                'containerId' => OptionBehavior::class,
                'redirected' => OptionTargetBehavior::class,
                'definitionOptions' => OptionDefaultsBehavior::class,
            ],
            StandAsideOptionBehaviorsOwner::class => [
                'getterOnly' => 'yii\base\InvalidCallException: Setting read-only property: '
                    . OptionBehavior::class . '::readOnly',
                'setOverride' => SetOverrideBehavior::class,
                'constructorOverride' => self::unknown(ConstructorBehavior::class, 'missing'),
                'mappedAway' => self::unknown(NotAttachableObject::class, 'missing'),
                'factory' => 'yii\base\UnknownMethodException: Calling unknown method: '
                    . NotAttachableObject::class . '::attach()',
                'unknownId' => 'yii\di\NotInstantiableException: Failed to instantiate component or class '
                    . '"notConfiguredId".',
                'nullDunder' => self::unknown(OptionBehavior::class, '__class'),
                'unionClass' => self::unknown(OptionBehavior::class, 'missing'),
                'classString' => OptionBehavior::class,
            ],
        ];
    }

    /**
     * @phpstan-return iterable<string, array{class-string<Component>, int|string, string}>
     */
    public static function entryProvider(): iterable
    {
        foreach (self::entryOutcomes() as $owner => $entries) {
            $short = substr($owner, (int) strrpos($owner, '\\') + 1);

            foreach ($entries as $name => $outcome) {
                yield "{$short} {$name}" => [$owner, $name, $outcome];
            }
        }
    }

    /**
     * Returns the method, case and recorded outcome of every case of a fixture of {@see callOutcomes()}.
     *
     * @phpstan-return iterable<string, array{string, string, string}>
     */
    private static function cases(string $fixture): iterable
    {
        foreach (self::callOutcomes()[$fixture] ?? [] as $method => $cases) {
            foreach ($cases as $case => $outcome) {
                yield "{$method} {$case}" => [$method, $case, $outcome];
            }
        }
    }

    /**
     * Returns the outcome of Yii rejecting an option that the class can't assign.
     */
    private static function unknown(string $class, string $option): string
    {
        return "yii\\base\\UnknownPropertyException: Setting unknown property: {$class}::{$option}";
    }
}
