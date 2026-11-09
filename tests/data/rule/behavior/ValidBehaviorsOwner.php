<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\behavior;

use yii\base\{Behavior, Component};
use yii2\extensions\phpstan\tests\support\behavior\{
    AttachableBehavior,
    BehaviorContract,
    BehaviorFactory,
    InvokableFactory,
    MagicCallClass,
    NotAttachableObject,
    PlainStaticAttach,
    PublicAttachObject,
    RedirectedBehavior,
    StaticAttachObject,
    StaticAttachOnly,
};

/**
 * Component whose `behaviors()` returns only definitions that Yii attaches, including classes that aren't behaviors.
 */
final class ValidBehaviorsOwner extends Component
{
    /**
     * @return array<array-key, mixed>
     */
    public function behaviors(): array
    {
        return [
            'aliasChain' => 'aliasA',
            'attachable' => AttachableBehavior::class,
            'base' => Behavior::class,
            'callable' => [
                BehaviorFactory::class,
                'create',
            ],
            'classLoses' => [
                'class' => NotAttachableObject::class,
                '__class' => AttachableBehavior::class,
            ],
            'closure' => static fn(): AttachableBehavior => new AttachableBehavior(),
            'configured' => ['class' => AttachableBehavior::class],
            'configuredId' => ['class' => 'attachableBehaviorId'],
            'containerId' => 'attachableBehaviorId',
            'contract' => BehaviorContract::class,
            'dunder' => ['__class' => StaticAttachObject::class],
            'duplicate' => AttachableBehavior::class,
            'duplicate' => NotAttachableObject::class,
            'instance' => new AttachableBehavior(),
            'instanceAlias' => 'instanceAlias',
            'invokable' => new InvokableFactory(),
            'magicCall' => MagicCallClass::class,
            'plainStaticAttach' => PlainStaticAttach::class,
            'publicAttach' => PublicAttachObject::class,
            'redirected' => RedirectedBehavior::class,
            'staticAttach' => StaticAttachObject::class,
            'staticAttachOnly' => StaticAttachOnly::class,
        ];
    }
}
