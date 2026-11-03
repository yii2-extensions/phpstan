<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\createobject;

use yii\base\Component;

/**
 * Component target whose `ensureBehaviors()` attaches {@see ComponentBehavior} once.
 */
final class ComponentEnsureBehaviorsTarget extends Component
{
    private bool $ensured = false;

    public function ensureBehaviors(): void
    {
        parent::ensureBehaviors();

        if ($this->ensured === false) {
            $this->ensured = true;

            $this->attachBehavior('ensured', new ComponentBehavior());
        }
    }
}
