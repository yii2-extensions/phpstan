<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\traitproperty;

/**
 * Subclass of {@see ProtectedCountObject} that redeclares `$count` as a plain public property.
 */
final class PublicCountObject extends ProtectedCountObject
{
    public int $count = 0;
}
