<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\behavior;

use Closure;
use yii\base\Component;
use yii2\extensions\phpstan\tests\support\behavior\{
    BehaviorOwner,
    MappedAwayBehavior,
    OptionBehavior,
    OptionRedirectedBehavior,
};

/**
 * `attachBehavior()` and `attachBehaviors()` calls with behavior configurations, grouped by expected outcome: reported,
 * valid and stood aside.
 *
 * Each method returns its calls as closures by case name, so that tests run them with real Yii.
 */
final class OptionAttachCalls
{
    private const array CONFIGURATION = ['class' => OptionBehavior::class, 'missing' => 1];
    private const array DEFINITIONS = ['constant' => ['class' => OptionBehavior::class, 'missing' => 1]];

    /**
     * @return array<string, Closure(BehaviorOwner): void>
     */
    public function reported(): array
    {
        return [
            'unknown' => static function (BehaviorOwner $owner): void {
                $owner->attachBehavior(
                    'unknown',
                    [
                        'class' => OptionBehavior::class,
                        'missing' => 1,
                    ],
                );
            },
            'named' => static function (BehaviorOwner $owner): void {
                $owner->attachBehavior(
                    behavior: [
                        'class' => OptionRedirectedBehavior::class,
                        'missing' => 1,
                    ],
                    name: 'named',
                );
            },
            'onComponent' => static function (Component $component): void {
                $component->attachBehavior(
                    'onComponent',
                    [
                        'class' => OptionBehavior::class,
                        'hidden' => 1,
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
                            'class' => OptionBehavior::class,
                            'missing' => 1,
                        ],
                    ],
                );
            },
            'anonymous' => static function (BehaviorOwner $owner): void {
                $owner->attachBehaviors(
                    [
                        [
                            'class' => OptionBehavior::class,
                            'missing' => 1,
                        ],
                    ],
                );
            },
            'constantList' => static function (BehaviorOwner $owner): void {
                $owner->attachBehaviors(self::DEFINITIONS);
            },
        ];
    }

    /**
     * @param array<string, mixed> $configuration
     *
     * @return array<string, Closure(BehaviorOwner): void>
     */
    public function standAside(array $configuration, string $name): array
    {
        return [
            'overridden' => static function (): void {
                (new OverridingAttachOwner())->attachBehavior(
                    'overridden',
                    ['class' => OptionBehavior::class, 'missing' => 1],
                );
            },
            'dynamicConfiguration' => static function (BehaviorOwner $owner) use ($configuration): void {
                $owner->attachBehavior('dynamicConfiguration', $configuration);
            },
            'dynamicKey' => static function (BehaviorOwner $owner) use ($name): void {
                $owner->attachBehavior('dynamicKey', ['class' => OptionBehavior::class, $name => 1]);
            },
            'mappedAway' => static function (BehaviorOwner $owner): void {
                $owner->attachBehavior('mappedAway', ['class' => MappedAwayBehavior::class, 'missing' => 1]);
            },
        ];
    }

    /**
     * @return array<string, Closure(BehaviorOwner): void>
     */
    public function valid(): array
    {
        return [
            'property' => static function (BehaviorOwner $owner): void {
                $owner->attachBehavior('property', ['class' => OptionBehavior::class, 'label' => 'value']);
            },
            'setter' => static function (BehaviorOwner $owner): void {
                $owner->attachBehaviors(['setter' => ['class' => OptionBehavior::class, 'writable' => 'value']]);
            },
            'redirected' => static function (BehaviorOwner $owner): void {
                $owner->attachBehavior('redirected', ['class' => OptionRedirectedBehavior::class, 'onlyOnTarget' => 1]);
            },
        ];
    }
}
