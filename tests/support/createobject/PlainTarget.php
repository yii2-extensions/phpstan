<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\createobject;

/**
 * Target that doesn't extend `yii\base\BaseObject`.
 */
final class PlainTarget
{
    public string $name = '';
}
