<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\traitproperty;

use yii\base\Widget;

/**
 * Widget with a public `$trigger` property.
 */
class TriggerWidget extends Widget
{
    public mixed $trigger = null;

    public function run(): string
    {
        return static::class;
    }
}
