<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\behavior;

use Closure;
use Yii;
use yii\base\Behavior;
use yii2\extensions\phpstan\tests\support\behavior\{
    BehaviorOwner,
    MappedAwayBehavior,
    NotAttachableObject,
    OptionBehavior,
    OptionRedirectedBehavior,
    OptionTargetBehavior,
    PlainClass,
};

/**
 * `Yii::createObject()` calls with behavior configurations in `as` options, grouped by expected outcome: reported,
 * valid and stood aside.
 *
 * Each method returns its calls as closures by case name, so that tests run them with real Yii.
 */
final class AsBehaviorOptions
{
    private const array CONSTANT = [
        'class' => BehaviorOwner::class,
        'as constant' => ['class' => OptionBehavior::class, 'missing' => 1],
    ];

    public bool $flag = false;

    /**
     * @return array<string, Closure(): object>
     */
    public function reported(): array
    {
        return [
            'unknown' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as unknown' => [
                        'class' => OptionBehavior::class,
                        'missing' => 1,
                    ],
                ],
            ),
            'dunderWins' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as dunderWins' => [
                        '__class' => OptionBehavior::class,
                        'class' => OptionTargetBehavior::class,
                        'onlyOnTarget' => 1,
                    ],
                ],
            ),
            // `__class` alone decides the check, so the plain `class` doesn't matter
            'dunderAccepted' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as dunderAccepted' => [
                        '__class' => OptionBehavior::class,
                        'class' => PlainClass::class,
                        'missing' => 1,
                    ],
                ],
            ),
            // the supplied class passes the check; the container creates another behavior
            'redirected' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as redirected' => [
                        'class' => OptionRedirectedBehavior::class,
                        'missing' => 1,
                    ],
                ],
            ),
            'leadingSlash' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as leadingSlash' => [
                        'class' => '\yii2\extensions\phpstan\tests\support\behavior\OptionBehavior',
                        'missing' => 1,
                    ],
                ],
            ),
            // `Yii::createObject()` assigns the options before `attachBehavior()` runs
            'attachOverride' => static fn(): object => Yii::createObject(
                [
                    'class' => OverridingAttachOwner::class,
                    'as attachOverride' => [
                        'class' => OptionBehavior::class,
                        'missing' => 1,
                    ],
                ],
            ),
            'params' => static fn(): object => Yii::createObject(
                BehaviorOwner::class,
                [
                    [
                        'as params' => ['class' => OptionBehavior::class, 'missing' => 1],
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
            // `Component::__set()` rejects these classes first, which `invalidAsDefinition` reports or stands aside on
            'behaviorItself' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as behaviorItself' => ['class' => Behavior::class, 'missing' => 1],
                ],
            ),
            'containerId' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as containerId' => ['class' => 'optionBehaviorId', 'missing' => 1],
                ],
            ),
            'mappedAway' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as mappedAway' => ['class' => MappedAwayBehavior::class, 'missing' => 1],
                ],
            ),
            'nullDunder' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as nullDunder' => ['__class' => null, 'class' => OptionBehavior::class, 'missing' => 1],
                ],
            ),
            'union' => fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as union' => $this->flag
                        ? ['class' => OptionBehavior::class, 'missing' => 1]
                        : ['class' => OptionBehavior::class, 'label' => 1],
                ],
            ),
            'getterOnly' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as getterOnly' => ['class' => OptionBehavior::class, 'readOnly' => 1],
                ],
            ),
            'setOverride' => static fn(): object => Yii::createObject(
                [
                    'class' => SetOverridingOwner::class,
                    'as setOverride' => ['class' => OptionBehavior::class, 'missing' => 1],
                ],
            ),
            // a `BaseObject` treats the `as` option as an unknown property of its own
            'baseObjectOwner' => static fn(): object => Yii::createObject(
                [
                    'class' => NotAttachableObject::class,
                    'as baseObjectOwner' => ['class' => OptionBehavior::class, 'missing' => 1],
                ],
            ),
            'notAsKey' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'As notAsKey' => ['class' => OptionBehavior::class, 'missing' => 1],
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
            'property' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as property' => ['class' => OptionBehavior::class, 'label' => 'value'],
                ],
            ),
            'setter' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as setter' => ['class' => OptionBehavior::class, 'writable' => 'value'],
                ],
            ),
            'redirected' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as redirected' => ['class' => OptionRedirectedBehavior::class, 'onlyOnTarget' => 1],
                ],
            ),
        ];
    }
}
