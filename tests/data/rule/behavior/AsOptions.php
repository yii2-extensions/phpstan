<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\behavior;

use Closure;
use Yii;
use yii\base\Behavior;
use yii2\extensions\phpstan\tests\support\behavior\{
    AbstractBehavior,
    AbstractBoundBehavior,
    AttachableBehavior,
    BehaviorContract,
    BehaviorFactory,
    BehaviorOwner,
    BoundToBehaviorObject,
    ComponentBoundBehavior,
    CycleBehavior,
    FactoryBoundBehavior,
    InterfaceBoundBehavior,
    InvokableBoundBehavior,
    MappedAwayBehavior,
    NotAttachableObject,
    NotBehaviorComponent,
    PlainClass,
    PublicAttachObject,
    RedirectedBehavior,
    SingletonBoundBehavior,
};

/**
 * `Yii::createObject()` calls with `as` options, grouped by expected outcome: reported, valid and stood aside.
 *
 * Each method returns its calls as closures by case name, so that tests run them with real Yii.
 */
final class AsOptions
{
    private const array CONSTANT = ['class' => BehaviorOwner::class, 'as constant' => PlainClass::class];

    public bool $flag = false;

    public function __construct(public PlainClass $plain = new PlainClass()) {}

    /**
     * @return array<string, Closure(): object>
     */
    public function reported(): array
    {
        return [
            'plainClass' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as plainClass' => PlainClass::class,
                ],
            ),
            'notAttachable' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as notAttachable' => NotAttachableObject::class,
                ],
            ),
            // `behaviors()` would attach it through its public `attach()`
            'publicAttach' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as publicAttach' => PublicAttachObject::class,
                ],
            ),
            'component' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as component' => NotBehaviorComponent::class,
                ],
            ),
            // the subclass check is strict
            'behavior' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as behavior' => Behavior::class,
                ],
            ),
            'behaviorClass' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as behaviorClass' => ['class' => Behavior::class],
                ],
            ),
            // the container binds both to a behavior, but Yii checks the supplied class first
            'contract' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as contract' => BehaviorContract::class,
                ],
            ),
            'boundObject' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as boundObject' => BoundToBehaviorObject::class,
                ],
            ),
            'leadingSlash' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as leadingSlash' => '\yii2\extensions\phpstan\tests\support\behavior\PlainClass',
                ],
            ),
            'dunderWins' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as dunderWins' => [
                        '__class' => PlainClass::class,
                        'class' => AttachableBehavior::class,
                    ],
                ],
            ),
            'nullDunder' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as nullDunder' => [
                        '__class' => null,
                        'class' => PlainClass::class,
                    ],
                ],
            ),
            'nullClass' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as nullClass' => ['class' => null],
                ],
            ),
            'emptyArray' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as emptyArray' => [],
                ],
            ),
            // unlike `behaviors()`, `as` options never call a callable array
            'callable' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as callable' => [BehaviorFactory::class, 'create'],
                ],
            ),
            'null' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as null' => null,
                ],
            ),
            'integer' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as integer' => 1,
                ],
            ),
            'finalObject' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as finalObject' => new BehaviorFactory(),
                ],
            ),
            'newObject' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as newObject' => new PlainClass(),
                ],
            ),
            'mappedAway' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as mappedAway' => MappedAwayBehavior::class,
                ],
            ),
            'mappedComponent' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as mappedComponent' => ['class' => ComponentBoundBehavior::class],
                ],
            ),
            'spaced' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as  spaced ' => PlainClass::class,
                ],
            ),
            // the override doesn't run: `Component::__set()` throws first
            'attachOverride' => static fn(): object => Yii::createObject(
                [
                    'class' => OverridingAttachOwner::class,
                    'as attachOverride' => PlainClass::class,
                ],
            ),
            'params' => static fn(): object => Yii::createObject(
                BehaviorOwner::class,
                [
                    ['as params' => PlainClass::class],
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
            // class names unknown to the analysis may exist at runtime
            'containerId' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as containerId' => 'attachableBehaviorId',
                ],
            ),
            'unknownId' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as unknownId' => 'notConfiguredId',
                ],
            ),
            'emptyString' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as emptyString' => '',
                ],
            ),
            'abstract' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as abstract' => AbstractBehavior::class,
                ],
            ),
            'union' => fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as union' => $this->flag ? PlainClass::class : AttachableBehavior::class,
                ],
            ),
            'objectOrClass' => fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as objectOrClass' => $this->flag ? new PlainClass() : AttachableBehavior::class,
                ],
            ),
            'arrayUnion' => fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as arrayUnion' => $this->flag
                        ? ['class' => PlainClass::class]
                        : ['class' => AttachableBehavior::class],
                ],
            ),
            'maybeNullDunder' => fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as maybeNullDunder' => [
                        '__class' => $this->flag ? PlainClass::class : null,
                        'class' => AttachableBehavior::class,
                    ],
                ],
            ),
            'nullDunderBehavior' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as nullDunderBehavior' => [
                        '__class' => null,
                        'class' => AttachableBehavior::class,
                    ],
                ],
            ),
            'integerClass' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as integerClass' => ['class' => 1],
                ],
            ),
            // a subclass may implement `ArrayAccess`
            'typedObject' => fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as typedObject' => $this->plain,
                ],
            ),
            'closureClassName' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as closureClassName' => static fn(): string => PlainClass::class,
                ],
            ),
            'factory' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as factory' => FactoryBoundBehavior::class,
                ],
            ),
            'singleton' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as singleton' => SingletonBoundBehavior::class,
                ],
            ),
            'invokable' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as invokable' => InvokableBoundBehavior::class,
                ],
            ),
            'abstractBound' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as abstractBound' => AbstractBoundBehavior::class,
                ],
            ),
            'interfaceBound' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as interfaceBound' => InterfaceBoundBehavior::class,
                ],
            ),
            'cycle' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as cycle' => CycleBehavior::class,
                ],
            ),
            'attachOverrideMapped' => static fn(): object => Yii::createObject(
                [
                    'class' => OverridingAttachOwner::class,
                    'as attachOverrideMapped' => MappedAwayBehavior::class,
                ],
            ),
            'setOverride' => static fn(): object => Yii::createObject(
                [
                    'class' => SetOverridingOwner::class,
                    'as setOverride' => PlainClass::class,
                ],
            ),
            'baseObjectOwner' => static fn(): object => Yii::createObject(
                [
                    'class' => PublicAttachObject::class,
                    'as baseObjectOwner' => PlainClass::class,
                ],
            ),
            'notAsKey' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'As notAsKey' => PlainClass::class,
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
            'attachable' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as attachable' => AttachableBehavior::class,
                ],
            ),
            'configured' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as configured' => ['class' => AttachableBehavior::class],
                ],
            ),
            'dunder' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as dunder' => [
                        '__class' => AttachableBehavior::class,
                        'class' => PlainClass::class,
                    ],
                ],
            ),
            'leadingSlash' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as leadingSlash' => '\yii2\extensions\phpstan\tests\support\behavior\AttachableBehavior',
                ],
            ),
            'instance' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as instance' => new AttachableBehavior(),
                ],
            ),
            'closure' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as closure' => static fn(): AttachableBehavior => new AttachableBehavior(),
                ],
            ),
            'firstClassCallable' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as firstClassCallable' => BehaviorFactory::create(...),
                ],
            ),
            'redirected' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as redirected' => RedirectedBehavior::class,
                ],
            ),
            // container IDs are compared as written, so the binding of the class doesn't apply
            'leadingSlashMapped' => static fn(): object => Yii::createObject(
                [
                    'class' => BehaviorOwner::class,
                    'as leadingSlashMapped' => '\yii2\extensions\phpstan\tests\support\behavior\MappedAwayBehavior',
                ],
            ),
        ];
    }
}
