<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\behavior;

use yii\base\Component;
use yii2\extensions\phpstan\tests\support\behavior\OptionBehavior;

/**
 * Component that overrides `ensureBehaviors()`, so that its `behaviors()` list is never attached.
 */
final class EnsureOverrideOptionOwner extends Component
{
    /**
     * @return array<array-key, mixed>
     */
    public function behaviors(): array
    {
        return ['unknown' => ['class' => OptionBehavior::class, 'missing' => 1]];
    }

    public function ensureBehaviors(): void {}
}
