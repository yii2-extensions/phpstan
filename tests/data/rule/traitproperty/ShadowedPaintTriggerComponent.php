<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\traitproperty;

/**
 * Subclass of {@see PaintTriggerComponent} that redeclares `$trigger` without a hook and inherits the trait's set hook.
 */
final class ShadowedPaintTriggerComponent extends PaintTriggerComponent
{
    public mixed $trigger = null;
}
