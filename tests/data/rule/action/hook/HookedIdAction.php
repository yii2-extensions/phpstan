<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\action\hook;

use yii\base\Action;
use yii2\extensions\phpstan\tests\support\action\PaintBehavior;

/**
 * Action with a typed property whose `id` set hook attaches {@see PaintBehavior} before `Action::__construct()` applies
 * the configuration.
 */
final class HookedIdAction extends Action
{
    public int $count = 0;

    public $id {
        set {
            $this->attachBehavior('paint', new PaintBehavior());

            $this->id = $value;
        }
    }

    public function run(): string
    {
        return self::class;
    }
}
