<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\createobject;

use yii\base\Component;

/**
 * Abstract component target.
 */
abstract class ComponentAbstractTarget extends Component
{
    public int $count = 0;
}
