<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\traitproperty;

/**
 * Subclass of {@see ProtectedTriggerComponent} that redeclares `$trigger` as a plain public property.
 */
final class PublicTriggerComponent extends ProtectedTriggerComponent
{
    public mixed $trigger = null;
}
