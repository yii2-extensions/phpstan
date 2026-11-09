<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\behavior;

use yii\base\Component;
use yii2\extensions\phpstan\tests\support\behavior\{
    AttachableBehavior,
    MappedAwayBehavior,
    NotAttachableObject,
    PlainClass,
    PrivateStaticAttachClass,
    ProtectedAttachClass,
    ProtectedAttachObject,
    ProtectedStaticAttachObject,
};

/**
 * Component whose `behaviors()` returns only definitions that Yii can't attach; its own return type accepts arrays.
 */
final class ReportedBehaviorsOwner extends Component
{
    /**
     * @return array<array-key, mixed>
     */
    public function behaviors(): array
    {
        return [
            'aliasChain' => 'notAttachableAlias',
            'configured' => ['class' => NotAttachableObject::class],
            'dunderWins' => ['__class' => PlainClass::class, 'class' => AttachableBehavior::class],
            'duplicate' => AttachableBehavior::class,
            'duplicate' => NotAttachableObject::class,
            'emptyArray' => [],
            'leadingSlash' => '\\' . NotAttachableObject::class,
            'mappedAway' => MappedAwayBehavior::class,
            'notAttachable' => NotAttachableObject::class,
            'notCallable' => [AttachableBehavior::class],
            'nullClass' => ['class' => null, 'label' => 'value'],
            'plainClass' => PlainClass::class,
            'privateStaticAttach' => PrivateStaticAttachClass::class,
            'protectedAttach' => ProtectedAttachObject::class,
            'protectedAttachClass' => ProtectedAttachClass::class,
            'protectedStaticAttach' => ProtectedStaticAttachObject::class,
            PlainClass::class,
        ];
    }
}
