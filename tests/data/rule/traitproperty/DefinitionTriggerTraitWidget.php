<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\traitproperty;

/**
 * Subclass of {@see TriggerWidget} whose container definition sets `$trigger`, redeclared through
 * {@see TriggerTrait} without a hook.
 */
final class DefinitionTriggerTraitWidget extends TriggerWidget
{
    use TriggerTrait;
}
