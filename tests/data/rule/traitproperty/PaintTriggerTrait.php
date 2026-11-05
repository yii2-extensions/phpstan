<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\traitproperty;

use yii2\extensions\phpstan\tests\support\action\PaintBehavior;

/**
 * Redeclares an inherited `$trigger` with a set hook that attaches {@see PaintBehavior}.
 */
trait PaintTriggerTrait
{
    public mixed $trigger = null {
        set {
            $this->attachBehavior('paint', new PaintBehavior());

            $this->trigger = $value;
        }
    }
}
