<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\createobject;

use yii\base\Component;

/**
 * Component target whose `behaviors()` attaches {@see ComponentBehavior}.
 */
class ComponentBehaviorsTarget extends Component
{
    public int $count = 0;

    public function behaviors(): array
    {
        return ['colour' => ComponentBehavior::class];
    }
}
