<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\traitproperty;

/**
 * Subclass of {@see TriggerComponent} whose `$trigger` set hook comes from a trait of {@see NestedPaintTriggerTrait}.
 */
final class NestedPaintTriggerComponent extends TriggerComponent
{
    use NestedPaintTriggerTrait;
}
