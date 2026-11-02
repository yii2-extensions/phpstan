<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\createobject;

use yii\base\BaseObject;

/**
 * Target whose `__set()` stores every unknown option.
 */
final class CustomSetterTarget extends BaseObject
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
