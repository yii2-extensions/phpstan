<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\createobject;

use yii\base\Component;

/**
 * Component target whose `__set()` stores every option without a native member.
 */
final class ComponentCustomSetterTarget extends Component
{
    /**
     * @var array<string, mixed>
     */
    public array $extra = [];

    public function __set($name, $value): void
    {
        $this->extra[$name] = $value;
    }
}
