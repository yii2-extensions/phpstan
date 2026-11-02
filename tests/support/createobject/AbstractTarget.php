<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\createobject;

use yii\base\BaseObject;

/**
 * Abstract target that the container can't instantiate.
 */
abstract class AbstractTarget extends BaseObject
{
    public string $name = '';
}
