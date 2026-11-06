<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\traitproperty;

/**
 * Subclass of {@see TriggerAction} whose `$trigger` set hook comes from {@see PaintTriggerTrait}.
 */
final class PaintTriggerAction extends TriggerAction
{
    use PaintTriggerTrait;
}
