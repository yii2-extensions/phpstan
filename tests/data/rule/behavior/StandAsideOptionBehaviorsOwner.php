<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\behavior;

use yii\base\Component;
use yii2\extensions\phpstan\tests\support\behavior\{
    ConstructorBehavior,
    FactoryBoundBehavior,
    MappedAwayBehavior,
    OptionBehavior,
    SetOverrideBehavior,
};

/**
 * Component whose `behaviors()` returns configurations whose unknown options the rule can't prove, or that another
 * rule reports.
 */
final class StandAsideOptionBehaviorsOwner extends Component
{
    public bool $flag = false;

    /**
     * @return array<array-key, mixed>
     */
    public function behaviors(): array
    {
        return [
            // Yii throws for a read-only property, not for an unknown one
            'getterOnly' => ['class' => OptionBehavior::class, 'readOnly' => 1],
            'setOverride' => ['class' => SetOverrideBehavior::class, 'missing' => 1],
            'constructorOverride' => ['class' => ConstructorBehavior::class, 'missing' => 1],
            // the container creates a class that isn't a behavior, which `invalidDefinition` reports
            'mappedAway' => ['class' => MappedAwayBehavior::class, 'missing' => 1],
            'factory' => ['class' => FactoryBoundBehavior::class, 'missing' => 1],
            'unknownId' => ['class' => 'notConfiguredId', 'missing' => 1],
            'nullDunder' => ['__class' => null, 'class' => OptionBehavior::class, 'missing' => 1],
            'unionClass' => ['class' => $this->flag ? OptionBehavior::class : 'optionBehaviorId', 'missing' => 1],
            'classString' => OptionBehavior::class,
        ];
    }
}
