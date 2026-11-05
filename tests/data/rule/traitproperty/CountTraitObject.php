<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\traitproperty;

/**
 * Subclass of {@see CountObject} that redeclares `$count` through {@see CountTrait}, without a hook.
 */
final class CountTraitObject extends CountObject
{
    use CountTrait;
}
