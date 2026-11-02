<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\createobject;

use yii\base\BaseObject;

/**
 * Configurable target bound in the container of the analysis configuration.
 */
final class SingletonTarget extends BaseObject
{
    public string $name = '';
}
