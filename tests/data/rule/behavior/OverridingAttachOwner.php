<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\behavior;

use yii\base\{Behavior, Component};
use yii2\extensions\phpstan\tests\support\behavior\AttachableBehavior;

/**
 * Component that overrides both attach methods, so that their arguments don't reach Yii's attachment.
 */
final class OverridingAttachOwner extends Component
{
    /**
     * @param array<array-key, mixed>|Behavior|string $behavior
     */
    public function attachBehavior($name, $behavior): Behavior
    {
        return new AttachableBehavior();
    }

    /**
     * @param array<array-key, mixed> $behaviors
     */
    public function attachBehaviors($behaviors): void {}
}
