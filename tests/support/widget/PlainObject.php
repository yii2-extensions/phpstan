<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\widget;

use yii\base\BaseObject;

/**
 * Configurable object that isn't a widget.
 */
final class PlainObject extends BaseObject
{
    public string $name = '';
}
