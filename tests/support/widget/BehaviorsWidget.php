<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\widget;

use yii2\extensions\phpstan\tests\support\createobject\ComponentBehavior;

/**
 * Subclass of {@see TargetWidget} whose `behaviors()` attaches {@see ComponentBehavior}.
 */
class BehaviorsWidget extends TargetWidget
{
    public function behaviors(): array
    {
        return ['colouring' => ComponentBehavior::class];
    }
}
