<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\behavior;

use Closure;
use yii2\extensions\phpstan\tests\support\behavior\{BehaviorOwner, TypedOptionBehavior};

/**
 * `attachBehavior()` and `attachBehaviors()` calls with behavior configurations whose values the created behavior may
 * reject, grouped by expected outcome: reported, valid and stood aside.
 *
 * Each method returns its calls as closures by case name, so that tests run them with real Yii.
 */
final class TypeAttachCalls
{
    private const array CONFIGURATION = ['class' => TypedOptionBehavior::class, 'count' => 'all'];

    /**
     * @return array<string, Closure(BehaviorOwner): void>
     */
    public function reported(): array
    {
        return [
            'literal' => static function (BehaviorOwner $owner): void {
                $owner->attachBehavior(
                    'literal',
                    [
                        'class' => TypedOptionBehavior::class,
                        'count' => 'all',
                    ],
                );
            },
            'constantArgument' => static function (BehaviorOwner $owner): void {
                $owner->attachBehavior(
                    'constantArgument',
                    self::CONFIGURATION,
                );
            },
            'listed' => static function (BehaviorOwner $owner): void {
                $owner->attachBehaviors(
                    [
                        'listed' => [
                            'class' => TypedOptionBehavior::class,
                            'tags' => 'news',
                        ],
                    ],
                );
            },
        ];
    }

    /**
     * @param array<string, mixed> $configuration
     *
     * @return array<string, Closure(BehaviorOwner): void>
     */
    public function standAside(array $configuration): array
    {
        return [
            'overridden' => static function (): void {
                (new OverridingAttachOwner())->attachBehavior(
                    'overridden',
                    [
                        'class' => TypedOptionBehavior::class,
                        'count' => 'all',
                    ],
                );
            },
            'dynamicConfiguration' => static function (BehaviorOwner $owner) use ($configuration): void {
                $owner->attachBehavior(
                    'dynamicConfiguration',
                    $configuration,
                );
            },
        ];
    }

    /**
     * @return array<string, Closure(BehaviorOwner): void>
     */
    public function valid(): array
    {
        return [
            'coerced' => static function (BehaviorOwner $owner): void {
                $owner->attachBehavior(
                    'coerced',
                    [
                        'class' => TypedOptionBehavior::class,
                        'count' => '20',
                    ],
                );
            },
        ];
    }
}
