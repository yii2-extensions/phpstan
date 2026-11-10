<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\behavior;

use yii\base\Component;
use yii2\extensions\phpstan\tests\support\behavior\{
    OptionRedirectedBehavior,
    OptionTargetBehavior,
    TypedOptionBehavior,
};

/**
 * Component whose `behaviors()` returns configurations whose values the created behavior accepts, with type coercion.
 */
final class ValidTypeBehaviorsOwner extends Component
{
    /**
     * @return array<array-key, mixed>
     */
    public function behaviors(): array
    {
        return [
            'dunderWins' => [
                '__class' => OptionTargetBehavior::class,
                'class' => TypedOptionBehavior::class,
                'targetCount' => '3',
            ],
            'intForString' => [
                'class' => TypedOptionBehavior::class,
                'title' => 5,
            ],
            'intForStringSetter' => [
                'class' => TypedOptionBehavior::class,
                'guarded' => 5,
            ],
            'nullForNullable' => [
                'class' => TypedOptionBehavior::class,
                'limit' => null,
            ],
            'numericString' => [
                'class' => TypedOptionBehavior::class,
                'count' => '20',
            ],
            'owner' => [
                'class' => TypedOptionBehavior::class,
                'owner' => 'x',
            ],
            // PHPDoc types are not checked by Yii
            'phpDocOnly' => [
                'class' => TypedOptionBehavior::class,
                'documented' => 'all',
            ],
            'redirected' => [
                'class' => OptionRedirectedBehavior::class,
                'targetCount' => '3',
            ],
        ];
    }
}
