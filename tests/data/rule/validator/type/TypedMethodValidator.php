<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator\type;

use yii\validators\Validator;

/**
 * Validator with an `int` `method` option, mapped to `InlineValidator` by the inline analysis container.
 */
final class TypedMethodValidator extends Validator
{
    public int $method = 0;

    protected function validateValue($value): array|null
    {
        return null;
    }
}
