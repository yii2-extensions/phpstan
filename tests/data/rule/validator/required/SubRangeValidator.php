<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator\required;

use yii\validators\RangeValidator;

/**
 * Range validator subclass whose default range satisfies the parent `init()`.
 */
final class SubRangeValidator extends RangeValidator
{
    /**
     * @var array<int>
     */
    public $range = [1];
}
