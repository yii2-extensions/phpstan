<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\createobject;

use yii\base\Component;

/**
 * Component target bound in the container to a factory that ignores the configuration.
 */
final class ComponentBoundTarget extends Component
{
    public int $count = 0;
}
