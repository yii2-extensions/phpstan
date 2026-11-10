<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use yii\base\{Behavior, Component};
use yii2\extensions\phpstan\tests\data\rule\behavior\{
    ConstantOptionBehaviorsOwner,
    EnsureOverrideOptionOwner,
    OverridingAttachOwner,
    ReportedOptionBehaviorsOwner,
    StandAsideOptionBehaviorsOwner,
    ValidOptionBehaviorsOwner,
};
use yii2\extensions\phpstan\tests\support\behavior\{
    AttachableBehavior,
    BehaviorOwner,
    OptionBehavior,
    OptionDefaultsBehavior,
    OptionTargetBehavior,
};

use function sprintf;

/**
 * Data provider for the `yii2.behavior.unknownOption` rule tests.
 */
final class BehaviorUnknownOptionRuleProvider
{
    /**
     * Returns the expected diagnostics of `AsBehaviorOptions::reported()`, in report order.
     *
     * @phpstan-return list<array{string, string|null, string}>
     */
    public static function asReported(): array
    {
        return [
            self::asCase(
                "'missing' => 1,",
                "'as unknown' =>",
                'as unknown',
            ),
            self::asCase(
                "'onlyOnTarget' => 1,",
                "'as dunderWins' =>",
                'as dunderWins',
                OptionBehavior::class,
                'onlyOnTarget',
            ),
            self::asCase(
                "'missing' => 1,",
                "'as dunderAccepted' =>",
                'as dunderAccepted',
            ),
            self::asCase(
                "'missing' => 1,",
                "'as redirected' =>",
                'as redirected',
                OptionTargetBehavior::class,
            ),
            self::asCase(
                "'missing' => 1,",
                "'as leadingSlash' =>",
                'as leadingSlash',
            ),
            [
                "'missing' => 1,",
                "'as attachOverride' =>",
                self::asMessage(
                    'as attachOverride',
                    OverridingAttachOwner::class,
                    OptionBehavior::class,
                    'missing',
                ),
            ],
            self::asCase(
                "'as params' =>",
                null,
                'as params',
            ),
            self::asCase(
                'Yii::createObject(self::CONSTANT)',
                null,
                'as constant',
            ),
        ];
    }

    /**
     * Returns the expected diagnostics of `OptionAttachCalls::reported()`, in report order.
     *
     * @phpstan-return list<array{string, string|null, string}>
     */
    public static function attachReported(): array
    {
        $owner = BehaviorOwner::class;

        return [
            self::attachCase(
                "'missing' => 1,",
                "'unknown' =>",
                "'unknown'",
                'attachBehavior',
            ),
            [
                "'missing' => 1,",
                "'named' =>",
                self::message(
                    "'named'",
                    $owner,
                    'attachBehavior',
                    OptionTargetBehavior::class,
                    'missing',
                ),
            ],
            [
                "'hidden' => 1,",
                null,
                self::message(
                    "'onComponent'",
                    Component::class,
                    'attachBehavior',
                    OptionBehavior::class,
                    'hidden',
                ),
            ],
            self::attachCase(
                'self::CONFIGURATION,',
                null,
                "'constantArgument'",
                'attachBehavior',
            ),
            self::attachCase(
                "'missing' => 1,",
                "'listed' =>",
                "'listed'",
                'attachBehaviors',
            ),
            self::attachCase(
                "'missing' => 1,",
                "'anonymous' =>",
                '#0',
                'attachBehaviors',
            ),
            self::attachCase(
                'attachBehaviors(self::DEFINITIONS)',
                null,
                "'constant'",
                'attachBehaviors',
            ),
        ];
    }

    /**
     * Returns the message for a behavior configuration option that the behavior class can't assign.
     */
    public static function message(
        string $behavior,
        string $owner,
        string $method,
        string $behaviorClass,
        string $option,
    ): string {
        return sprintf(
            'Behavior %s in %s::%s() sets unknown property %s::$%s: the class has no public property or setter with '
                . 'that name.',
            $behavior,
            $owner,
            $method,
            $behaviorClass,
            $option,
        );
    }

    /**
     * @phpstan-return iterable<string, array{class-string, list<array{string, string|null, string}>}>
     */
    public static function reportedProvider(): iterable
    {
        $owner = ReportedOptionBehaviorsOwner::class;

        yield 'literal list with option lines' => [
            $owner,
            [
                self::behaviorsCase(
                    "'missing' => 1,",
                    "'unknown' =>",
                    "'unknown'",
                ),
                self::behaviorsCase(
                    "'hidden' => 1,",
                    null,
                    "'protected'",
                    OptionBehavior::class,
                    'hidden',
                ),
                self::behaviorsCase(
                    "'Label' => 1,",
                    null,
                    "'caseMismatch'",
                    OptionBehavior::class,
                    'Label',
                ),
                self::behaviorsCase(
                    "'as nested' =>",
                    null,
                    "'asKey'",
                    OptionBehavior::class,
                    'as nested',
                ),
                self::behaviorsCase(
                    "'on event' =>",
                    null,
                    "'onKey'",
                    OptionBehavior::class,
                    'on event',
                ),
                self::behaviorsCase(
                    "'missing' => 1,",
                    "'base' =>",
                    "'base'",
                    Behavior::class,
                ),
                self::behaviorsCase(
                    "'missing' => 1,",
                    "'afterValid' =>",
                    "'afterValid'",
                ),
                self::behaviorsCase(
                    "'onlyOnTarget' => 1,",
                    null,
                    "'dunderWins'",
                    OptionBehavior::class,
                    'onlyOnTarget',
                ),
                self::behaviorsCase(
                    "'missing' => 1,",
                    "'containerId' =>",
                    "'containerId'",
                ),
                self::behaviorsCase(
                    "'missing' => 1,",
                    "'aliasChain' =>",
                    "'aliasChain'",
                    AttachableBehavior::class,
                ),
                self::behaviorsCase(
                    "'missing' => 1,",
                    "'redirected' =>",
                    "'redirected'",
                    OptionTargetBehavior::class,
                ),
                self::behaviorsCase(
                    "'missing' => 1,",
                    "'definitionOptions' =>",
                    "'definitionOptions'",
                    OptionDefaultsBehavior::class,
                ),
                self::behaviorsCase(
                    "'missing' => 1,",
                    "'leadingSlash' =>",
                    "'leadingSlash'",
                ),
                self::behaviorsCase(
                    "'missing' => 1,",
                    "'constructorArgument' =>",
                    "'constructorArgument'",
                ),
                self::behaviorsCase(
                    "'missing' => 1,",
                    '// anonymous',
                    '#0',
                ),
            ],
        ];
        yield 'constant list at the return line' => [
            ConstantOptionBehaviorsOwner::class,
            [
                [
                    'return self::BEHAVIORS;',
                    null,
                    self::message(
                        "'constant'",
                        ConstantOptionBehaviorsOwner::class,
                        'behaviors',
                        OptionBehavior::class,
                        'missing',
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
        yield 'assignable options' => [[ValidOptionBehaviorsOwner::class]];
        yield 'ensureBehaviors() override' => [[EnsureOverrideOptionOwner::class]];
        yield 'unproven or other-rule configurations' => [[StandAsideOptionBehaviorsOwner::class]];
    }

    /**
     * @phpstan-return array{string, string|null, string}
     */
    private static function asCase(
        string $needle,
        string|null $start,
        string $option,
        string $behaviorClass = OptionBehavior::class,
        string $name = 'missing',
    ): array {
        return [
            $needle,
            $start,
            self::asMessage(
                $option,
                BehaviorOwner::class,
                $behaviorClass,
                $name,
            ),
        ];
    }

    /**
     * Returns the message for an `as` option configuration option that the behavior class can't assign.
     */
    private static function asMessage(string $option, string $owner, string $behaviorClass, string $name): string
    {
        return sprintf(
            "Yii::createObject() option '%s' of %s sets unknown property %s::\$%s: the class has no public property or "
                . 'setter with that name.',
            $option,
            $owner,
            $behaviorClass,
            $name,
        );
    }

    /**
     * @phpstan-return array{string, string|null, string}
     */
    private static function attachCase(string $needle, string|null $start, string $behavior, string $method): array
    {
        return [
            $needle,
            $start,
            self::message(
                $behavior,
                BehaviorOwner::class,
                $method,
                OptionBehavior::class,
                'missing',
            ),
        ];
    }

    /**
     * @phpstan-return array{string, string|null, string}
     */
    private static function behaviorsCase(
        string $needle,
        string|null $start,
        string $behavior,
        string $behaviorClass = OptionBehavior::class,
        string $option = 'missing',
    ): array {
        return [
            $needle,
            $start,
            self::message(
                $behavior,
                ReportedOptionBehaviorsOwner::class,
                'behaviors',
                $behaviorClass,
                $option,
            ),
        ];
    }
}
