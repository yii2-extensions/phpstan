<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\action\hook;

use yii\base\Action;
use yii2\extensions\phpstan\tests\support\action\PaintBehavior;

/**
 * Action whose `controller` set hook attaches {@see PaintBehavior} before `Action::__construct()` applies the
 * configuration.
 */
final class HookedControllerAction extends Action
{
    public $controller {
        set {
            $this->attachBehavior('paint', new PaintBehavior());

            $this->controller = $value;
        }
    }

    public function run(): string
    {
        return self::class;
    }
}
