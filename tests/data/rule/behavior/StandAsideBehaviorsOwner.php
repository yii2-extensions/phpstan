<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\behavior;

use yii\base\Component;
use yii2\extensions\phpstan\tests\support\behavior\{
    AbstractBehavior,
    AbstractPlainObject,
    AttachableBehavior,
    NotAttachableObject,
    NotBehaviorComponent,
    UnboundContract,
};

/**
 * Component whose `behaviors()` returns definitions the rule can't prove; Yii rejects some of them at runtime.
 */
final class StandAsideBehaviorsOwner extends Component
{
    public bool $flag = false;

    /**
     * @return array<array-key, mixed>
     */
    public function behaviors(): array
    {
        return [
            'abstract' => AbstractBehavior::class,
            'abstractPlain' => AbstractPlainObject::class,
            'closureClassName' => static fn(): string => NotAttachableObject::class,
            'component' => NotBehaviorComponent::class,
            'emptyClass' => ['class' => ''],
            'emptyId' => '',
            'factory' => 'factoryId',
            'maybeNullClass' => ['class' => $this->flag ? NotAttachableObject::class : null],
            'nullDunder' => [
                '__class' => null,
                'class' => AttachableBehavior::class,
            ],
            'singleton' => 'singletonId',
            'unboundInterface' => UnboundContract::class,
            'union' => $this->flag ? NotAttachableObject::class : AttachableBehavior::class,
            'unknownId' => 'notConfiguredId',
        ];
    }
}
