<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\createobject;

use yii\base\Component;

/**
 * Target whose `__set()` is {@see Component::__set()}, which also accepts events and behaviors.
 */
final class ComponentTarget extends Component
{
    public string $name = '';
}
