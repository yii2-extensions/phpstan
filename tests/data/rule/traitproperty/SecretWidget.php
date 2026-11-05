<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\traitproperty;

use yii\base\Widget;

/**
 * Widget with a protected `$secret` property.
 */
class SecretWidget extends Widget
{
    protected mixed $secret = null;

    public function run(): string
    {
        return static::class;
    }
}
