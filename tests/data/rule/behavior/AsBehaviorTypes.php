<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\behavior;

use Closure;
use Yii;
use yii2\extensions\phpstan\tests\support\behavior\{
    BehaviorOwner,
    NotAttachableObject,
    OptionRedirectedBehavior,
    TypedOptionBehavior,
};

/**
 * `Yii::createObject()` calls with behavior configurations in `as` options whose values the created behavior may
 * reject, grouped by expected outcome: reported, valid and stood aside.
 *
 * Each method returns its calls as closures by case name, so that tests run them with real Yii.
 */
final class AsBehaviorTypes
{
    private const array CONSTANT = [
        'class' => BehaviorOwner::class,
        'as constant' => ['class' => TypedOptionBehavior::class, 'count' => 'all'],
    ];

    /**
     * @return array<string, Closure(): object>
     */
    public function reported(): array
    {
        return [
            'literal' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as literal' => [
                        'class' => TypedOptionBehavior::class,
                        'count' => 'all',
                    ],
                ],
            ),
            // the supplied class passes the check; the container creates another behavior
            'redirected' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as redirected' => ['class' => OptionRedirectedBehavior::class, 'targetCount' => 'all'],
                ],
            ),
            // `Yii::createObject()` assigns the options before `attachBehavior()` runs
            'attachOverride' => static fn(): object => Yii::createObject(
                [
                    'class' => OverridingAttachOwner::class,
                    'as attachOverride' => ['class' => TypedOptionBehavior::class, 'count' => 'all'],
                ],
            ),
            'params' => static fn(): object => Yii::createObject(
                BehaviorOwner::class,
                [
                    [
                        'as params' => ['class' => TypedOptionBehavior::class, 'count' => 'all'],
                    ],
                ],
            ),
            'constant' => static fn(): object => Yii::createObject(self::CONSTANT),
        ];
    }

    /**
     * @return array<string, Closure(): object>
     */
    public function standAside(): array
    {
        return [
            'setOverride' => static fn(): object => Yii::createObject(
                [
                    'class' => SetOverridingOwner::class,
                    'as setOverride' => ['class' => TypedOptionBehavior::class, 'count' => 'all'],
                ],
            ),
            // a `BaseObject` treats the `as` option as an unknown property of its own
            'baseObjectOwner' => static fn(): object => Yii::createObject(
                [
                    'class' => NotAttachableObject::class,
                    'as baseObjectOwner' => ['class' => TypedOptionBehavior::class, 'count' => 'all'],
                ],
            ),
            // `Component::__set()` compares the prefix case-sensitively
            'notAsKey' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'As notAsKey' => ['class' => TypedOptionBehavior::class, 'count' => 'all'],
                ],
            ),
        ];
    }

    /**
     * @return array<string, Closure(): object>
     */
    public function valid(): array
    {
        return [
            'coerced' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as coerced' => ['class' => TypedOptionBehavior::class, 'count' => '20'],
                ],
            ),
        ];
    }
}
