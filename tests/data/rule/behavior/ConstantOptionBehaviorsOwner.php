<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\behavior;

use yii\base\Component;
use yii2\extensions\phpstan\tests\support\behavior\OptionBehavior;

/**
 * Component whose `behaviors()` returns a constant list with an unknown option, located at the return line.
 */
final class ConstantOptionBehaviorsOwner extends Component
{
    private const array BEHAVIORS = ['constant' => ['class' => OptionBehavior::class, 'missing' => 1]];

    /**
     * @return array<array-key, mixed>
     */
    public function behaviors(): array
    {
        return self::BEHAVIORS;
    }
}
