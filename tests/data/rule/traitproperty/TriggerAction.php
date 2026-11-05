<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\traitproperty;

use yii\base\Action;

/**
 * Action with a public `$trigger` property.
 */
class TriggerAction extends Action
{
    public mixed $trigger = null;

    public function run(): string
    {
        return static::class;
    }
}
