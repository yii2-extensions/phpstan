<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use yii\base\Component;
use yii2\extensions\phpstan\tests\data\rule\behavior\{
    ReportedTypeBehaviorsOwner,
    StandAsideTypeBehaviorsOwner,
    ValidTypeBehaviorsOwner,
};
use yii2\extensions\phpstan\tests\support\behavior\{
    BehaviorOwner,
    NotAttachableObject,
    OptionBehavior,
    OptionTargetBehavior,
    SetOverrideBehavior,
    TypedOptionBehavior,
};

use function sprintf;
use function strrpos;
use function substr;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\rules\BehaviorInvalidOptionTypeRuntimeTest} test cases.
 */
final class BehaviorInvalidOptionTypeRuntimeProvider
{
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
     * Recorded outcomes of the `TypeAttachCalls` and `AsBehaviorTypes` cases by fixture, method and case.
     *
     * @phpstan-return array<string, array<string, array<string, string>>>
     */
    public static function callOutcomes(): array
    {
        $count = self::property(TypedOptionBehavior::class, 'count', 'string', 'int');

        return [
            'attach' => [
                'reported' => [
                    'literal' => $count,
                    'constantArgument' => $count,
                    'listed' => self::setter('setTags', 'array', 'string'),
                ],
                'standAside' => [
                    'overridden' => 'none',
                    'dynamicConfiguration' => $count,
                ],
                'valid' => [
                    'coerced' => TypedOptionBehavior::class,
                ],
            ],
            'as' => [
                'reported' => [
                    'literal' => $count,
                    'redirected' => self::property(OptionTargetBehavior::class, 'targetCount', 'string', 'int'),
                    'attachOverride' => $count,
                    'params' => $count,
                    'constant' => $count,
                ],
                'standAside' => [
                    'setOverride' => 'none',
                    'baseObjectOwner' => 'yii\base\UnknownPropertyException: Setting unknown property: '
                        . NotAttachableObject::class . '::as baseObjectOwner',
                    'notAsKey' => 'yii\base\UnknownPropertyException: Setting unknown property: '
                        . BehaviorOwner::class . '::As notAsKey',
                ],
                'valid' => [
                    'coerced' => TypedOptionBehavior::class,
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
        $count = self::property(TypedOptionBehavior::class, 'count', 'string', 'int');

        return [
            ReportedTypeBehaviorsOwner::class => [
                0 => $count,
                'afterValid' => $count,
                'constructorArgument' => $count,
                'dunderWins' => $count,
                'nullForInt' => self::property(TypedOptionBehavior::class, 'count', 'null', 'int'),
                'property' => $count,
                'protectedProperty' => self::setter('setGuarded', 'string', 'array'),
                'redirected' => self::property(OptionTargetBehavior::class, 'targetCount', 'string', 'int'),
                'setter' => self::setter('setTags', 'array', 'string'),
            ],
            ValidTypeBehaviorsOwner::class => [
                'dunderWins' => OptionTargetBehavior::class,
                'intForString' => TypedOptionBehavior::class,
                'intForStringSetter' => TypedOptionBehavior::class,
                'nullForNullable' => TypedOptionBehavior::class,
                'numericString' => TypedOptionBehavior::class,
                'owner' => TypedOptionBehavior::class,
                'phpDocOnly' => TypedOptionBehavior::class,
                'redirected' => OptionTargetBehavior::class,
            ],
            StandAsideTypeBehaviorsOwner::class => [
                'getterOnly' => 'yii\base\InvalidCallException: Setting read-only property: '
                    . OptionBehavior::class . '::readOnly',
                'setOverride' => self::property(SetOverrideBehavior::class, 'options', 'string', 'array'),
                'unknownName' => 'yii\base\UnknownPropertyException: Setting unknown property: '
                    . TypedOptionBehavior::class . '::missing',
                'union' => TypedOptionBehavior::class,
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
     * Returns the outcome of PHP rejecting a value assigned to a typed property.
     */
    private static function property(string $class, string $property, string $given, string $type): string
    {
        return sprintf(
            'TypeError: Cannot assign %s to property %s::$%s of type %s',
            $given,
            $class,
            $property,
            $type,
        );
    }

    /**
     * Returns the outcome of PHP rejecting a value passed to a setter of {@see TypedOptionBehavior}.
     */
    private static function setter(string $method, string $type, string $given): string
    {
        return sprintf(
            'TypeError: %s::%s(): Argument #1 ($value) must be of type %s, %s given',
            TypedOptionBehavior::class,
            $method,
            $type,
            $given,
        );
    }
}
