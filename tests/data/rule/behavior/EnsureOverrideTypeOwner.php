<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\behavior;

use yii\base\Component;
use yii2\extensions\phpstan\tests\support\behavior\TypedOptionBehavior;

/**
 * Component that overrides `ensureBehaviors()`, so that its `behaviors()` list with a rejected value is never attached.
 */
final class EnsureOverrideTypeOwner extends Component
{
    /**
     * @return array<array-key, mixed>
     */
    public function behaviors(): array
    {
        return [
            'rejected' => [
                'class' => TypedOptionBehavior::class,
                'count' => 'all',
            ],
        ];
    }

    public function ensureBehaviors(): void {}
}
