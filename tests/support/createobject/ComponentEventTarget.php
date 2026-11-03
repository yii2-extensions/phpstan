<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\createobject;

use yii\base\Component;

/**
 * Component target whose `on()` also attaches {@see ComponentBehavior}.
 */
final class ComponentEventTarget extends Component
{
    /**
     * @param callable(): mixed $handler
     */
    public function on($name, $handler, $data = null, $append = true): void
    {
        parent::on($name, $handler, $data, $append);

        $this->attachBehavior('event', new ComponentBehavior());
    }
}
