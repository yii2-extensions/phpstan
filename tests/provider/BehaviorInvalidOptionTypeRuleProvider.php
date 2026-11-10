<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use yii2\extensions\phpstan\tests\data\rule\behavior\{
    EnsureOverrideTypeOwner,
    OverridingAttachOwner,
    ReportedTypeBehaviorsOwner,
    StandAsideTypeBehaviorsOwner,
    ValidTypeBehaviorsOwner,
};
use yii2\extensions\phpstan\tests\support\behavior\{BehaviorOwner, OptionTargetBehavior, TypedOptionBehavior};

use function sprintf;

/**
 * Data provider for the `yii2.behavior.invalidOptionType` rule tests.
 */
final class BehaviorInvalidOptionTypeRuleProvider
{
    /**
     * Member and accepted type of the `count` property of {@see TypedOptionBehavior}.
     */
    private const array COUNT = ['count', "'all'", 'the property', 'int'];

    /**
     * Member and accepted type of the `tags` setter of {@see TypedOptionBehavior}.
     */
    private const array TAGS = ['tags', "'news'", TypedOptionBehavior::class . '::setTags()', 'array'];

    /**
     * Returns the expected diagnostics of `AsBehaviorTypes::reported()`, in report order.
     *
     * @phpstan-return list<array{string, string|null, string}>
     */
    public static function asReported(): array
    {
        return [
            [
                "'count' => 'all',",
                "'as literal' =>",
                self::asMessage(
                    'as literal',
                    BehaviorOwner::class,
                ),
            ],
            [
                "'as redirected' =>",
                null,
                self::asMessage(
                    'as redirected',
                    BehaviorOwner::class,
                    OptionTargetBehavior::class,
                    'targetCount',
                ),
            ],
            [
                "'as attachOverride' =>",
                null,
                self::asMessage('as attachOverride', OverridingAttachOwner::class),
            ],
            [
                "'as params' =>",
                null,
                self::asMessage(
                    'as params',
                    BehaviorOwner::class,
                ),
            ],
            [
                'Yii::createObject(self::CONSTANT)',
                null,
                self::asMessage(
                    'as constant',
                    BehaviorOwner::class,
                ),
            ],
        ];
    }

    /**
     * Returns the expected diagnostics of `TypeAttachCalls::reported()`, in report order.
     *
     * @phpstan-return list<array{string, string|null, string}>
     */
    public static function attachReported(): array
    {
        return [
            [
                "'count' => 'all',",
                "'literal',",
                self::message(
                    "'literal'",
                    BehaviorOwner::class,
                    'attachBehavior',
                ),
            ],
            [
                'self::CONFIGURATION',
                null,
                self::message(
                    "'constantArgument'",
                    BehaviorOwner::class,
                    'attachBehavior',
                ),
            ],
            [
                "'tags' => 'news',",
                "'listed' => [",
                self::message(
                    "'listed'",
                    BehaviorOwner::class,
                    'attachBehaviors',
                    TypedOptionBehavior::class,
                    self::TAGS,
                ),
            ],
        ];
    }

    /**
     * @phpstan-return iterable<string, array{class-string, list<array{string, string|null, string}>}>
     */
    public static function reportedProvider(): iterable
    {
        yield 'literal list with option lines' => [
            ReportedTypeBehaviorsOwner::class,
            [
                [
                    "'count' => 'all',",
                    '// anonymous',
                    self::behaviorsMessage('#0'),
                ],
                [
                    "'count' => 'all',",
                    "'afterValid' =>",
                    self::behaviorsMessage("'afterValid'"),
                ],
                [
                    "'__construct()' =>",
                    null,
                    self::behaviorsMessage("'constructorArgument'"),
                ],
                [
                    "'count' => 'all',",
                    "'dunderWins' =>",
                    self::behaviorsMessage("'dunderWins'"),
                ],
                [
                    "'count' => null,",
                    "'nullForInt' =>",
                    self::behaviorsMessage(
                        "'nullForInt'",
                        TypedOptionBehavior::class,
                        [
                            'count',
                            'null',
                            'the property',
                            'int',
                        ],
                    ),
                ],
                [
                    "'count' => 'all',",
                    "'property' =>",
                    self::behaviorsMessage("'property'"),
                ],
                [
                    "'guarded' => [],",
                    "'protectedProperty' =>",
                    self::behaviorsMessage(
                        "'protectedProperty'",
                        TypedOptionBehavior::class,
                        [
                            'guarded',
                            'array{}',
                            TypedOptionBehavior::class . '::setGuarded()',
                            'string',
                        ],
                    ),
                ],
                [
                    "'targetCount' => 'all',",
                    "'redirected' =>",
                    self::behaviorsMessage(
                        "'redirected'",
                        OptionTargetBehavior::class,
                        [
                            'targetCount',
                            "'all'",
                            'the property',
                            'int',
                        ],
                    ),
                ],
                [
                    "'tags' => 'news',",
                    "'setter' =>",
                    self::behaviorsMessage(
                        "'setter'",
                        TypedOptionBehavior::class,
                        self::TAGS,
                    ),
                ],
            ],
        ];
    }

    /**
     * @phpstan-return iterable<string, array{list<class-string>}>
     */
    public static function standAsideProvider(): iterable
    {
        yield 'accepted values' => [[ValidTypeBehaviorsOwner::class]];
        yield 'ensureBehaviors() override' => [[EnsureOverrideTypeOwner::class]];
        yield 'unproven or other-rule values' => [[StandAsideTypeBehaviorsOwner::class]];
    }

    /**
     * Returns the message for a value of an `as` option configuration that the behavior class never accepts.
     */
    private static function asMessage(
        string $option,
        string $owner,
        string $behaviorClass = TypedOptionBehavior::class,
        string $name = 'count',
    ): string {
        return sprintf(
            "Yii::createObject() option '%s' of %s sets %s::\$%s to 'all', but the property accepts only int, even "
                . 'with type coercion.',
            $option,
            $owner,
            $behaviorClass,
            $name,
        );
    }

    /**
     * Returns the message for a value of a `ReportedTypeBehaviorsOwner::behaviors()` configuration.
     *
     * @param array{string, string, string, string} $target Option, value type, member and accepted type.
     */
    private static function behaviorsMessage(
        string $behavior,
        string $behaviorClass = TypedOptionBehavior::class,
        array $target = self::COUNT,
    ): string {
        return self::message(
            $behavior,
            ReportedTypeBehaviorsOwner::class,
            'behaviors',
            $behaviorClass,
            $target,
        );
    }

    /**
     * Returns the message for a value of a behavior configuration that the behavior class never accepts.
     *
     * @param array{string, string, string, string} $target Option, value type, member and accepted type.
     */
    private static function message(
        string $behavior,
        string $owner,
        string $method,
        string $behaviorClass = TypedOptionBehavior::class,
        array $target = self::COUNT,
    ): string {
        return sprintf(
            'Behavior %s in %s::%s() sets %s::$%s to %s, but %s accepts only %s, even with type coercion.',
            $behavior,
            $owner,
            $method,
            $behaviorClass,
            ...$target,
        );
    }
}
