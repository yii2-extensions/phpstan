<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\traitproperty;

use yii\base\Action;

/**
 * Action with a protected `$secret` property.
 */
class SecretAction extends Action
{
    protected mixed $secret = null;

    public function run(): string
    {
        return static::class;
    }
}
