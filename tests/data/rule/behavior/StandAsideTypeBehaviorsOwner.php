<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\behavior;

use yii\base\Component;
use yii2\extensions\phpstan\tests\support\behavior\{OptionBehavior, SetOverrideBehavior, TypedOptionBehavior};

/**
 * Component whose `behaviors()` returns configurations whose rejected values the rule can't prove, or that another rule
 * reports.
 */
final class StandAsideTypeBehaviorsOwner extends Component
{
    public bool $flag = false;

    /**
     * @return array<array-key, mixed>
     */
    public function behaviors(): array
    {
        return [
            'getterOnly' => [
                'class' => OptionBehavior::class,
                'readOnly' => [],
            ],
            // Yii still assigns the public property, but the analyzer stands aside on a `__set()` override
            'setOverride' => [
                'class' => SetOverrideBehavior::class,
                'options' => 'all',
            ],
            // `unknownOption` reports a name without property or setter
            'unknownName' => [
                'class' => TypedOptionBehavior::class,
                'missing' => [],
            ],
            'union' => [
                'class' => TypedOptionBehavior::class,
                'count' => $this->flag ? 'all' : '20',
            ],
        ];
    }
}
