<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\action\hook;

use yii2\extensions\phpstan\tests\support\action\PaintBehavior;

/**
 * Redeclares `Action::$id` with a set hook that attaches {@see PaintBehavior}.
 */
trait IdHookTrait
{
    public $id {
        set {
            $this->attachBehavior('paint', new PaintBehavior());

            $this->id = $value;
        }
    }
}
