<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\behavior;

use yii\base\Component;
use yii2\extensions\phpstan\tests\support\behavior\NotAttachableObject;

/**
 * Component that overrides `ensureBehaviors()`, so that Yii's use of `behaviors()` isn't proven.
 */
final class EnsureOverrideBehaviorsOwner extends Component
{
    public function behaviors(): array
    {
        return ['notAttachable' => NotAttachableObject::class];
    }

    public function ensureBehaviors(): void {}
}
