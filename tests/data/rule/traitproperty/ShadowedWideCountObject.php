<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\traitproperty;

/**
 * Subclass of {@see WideCountObject} that redeclares `$count` without a hook and inherits the trait's set hook.
 */
final class ShadowedWideCountObject extends WideCountObject
{
    public int $count = 0;
}
