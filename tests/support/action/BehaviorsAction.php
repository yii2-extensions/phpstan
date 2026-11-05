<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\action;

use yii2\extensions\phpstan\tests\support\createobject\ComponentBehavior;

/**
 * Action whose `behaviors()` attaches a behavior with a `colour` property.
 */
final class BehaviorsAction extends TargetAction
{
    public function behaviors(): array
    {
        return ['paint' => ComponentBehavior::class];
    }
}
