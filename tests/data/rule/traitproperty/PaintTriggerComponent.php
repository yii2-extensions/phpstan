<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\traitproperty;

/**
 * Subclass of {@see TriggerComponent} whose `$trigger` set hook comes from {@see PaintTriggerTrait}.
 */
class PaintTriggerComponent extends TriggerComponent
{
    use PaintTriggerTrait;
}
