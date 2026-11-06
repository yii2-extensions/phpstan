<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\traitproperty;

/**
 * Subclass of {@see TriggerWidget} whose container definition sets `$trigger`, with the set hook of
 * {@see PaintTriggerTrait}.
 */
final class DefinitionPaintTriggerWidget extends TriggerWidget
{
    use PaintTriggerTrait;
}
