<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\traitproperty;

/**
 * Subclass of {@see CountObject} whose `$count` set hook comes from a trait of {@see NestedWideCountTrait}.
 */
final class NestedWideCountObject extends CountObject
{
    use NestedWideCountTrait;
}
