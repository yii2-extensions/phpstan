<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator\type;

use yii\validators\Validator;

/**
 * Validator with an `int` `type` option, mapped to `DateValidator` by the analysis container.
 */
final class IntegerTypeValidator extends Validator
{
    public int $type = 0;

    protected function validateValue($value): array|null
    {
        return null;
    }
}
