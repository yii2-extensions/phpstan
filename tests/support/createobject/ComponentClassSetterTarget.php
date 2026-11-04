<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\createobject;

use yii\base\Component;

/**
 * Component target whose `class` and `__class` setters attach {@see ComponentBehavior}.
 */
final class ComponentClassSetterTarget extends Component
{
    public function set__class(string $class): void
    {
        $this->attachBehavior($class, new ComponentBehavior());
    }

    public function setClass(string $class): void
    {
        $this->attachBehavior($class, new ComponentBehavior());
    }
}
