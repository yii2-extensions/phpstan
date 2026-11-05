<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\action\hook;

use yii\base\Action;

/**
 * Action whose `id` hooks only store the value.
 */
final class PassThroughHookAction extends Action
{
    public $id {
        get => $this->id;
        set => $value;
    }

    public function run(): string
    {
        return self::class;
    }
}
