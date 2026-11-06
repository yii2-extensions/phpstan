<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\traitproperty;

use yii\base\Action;

/**
 * Action with a typed `$count` property.
 */
class CountAction extends Action
{
    public int $count = 0;

    public function run(): string
    {
        return static::class;
    }
}
