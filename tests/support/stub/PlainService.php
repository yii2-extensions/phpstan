<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

/**
 * Stub concrete service class that doesn't extend `yii\base\BaseObject`, configured through a public property, for
 * container definition tests.
 */
final class PlainService
{
    public string $mode = 'async';
}
