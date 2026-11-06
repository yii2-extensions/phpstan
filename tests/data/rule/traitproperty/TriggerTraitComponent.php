<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\traitproperty;

/**
 * Subclass of {@see TriggerComponent} that redeclares `$trigger` through {@see TriggerTrait}, without a hook.
 */
final class TriggerTraitComponent extends TriggerComponent
{
    use TriggerTrait;
}
