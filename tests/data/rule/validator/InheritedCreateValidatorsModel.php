<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator;

use yii2\extensions\phpstan\tests\support\validator\CreateValidatorsOverrideBase;

/**
 * Model whose ancestor overrides `createValidators()`.
 */
final class InheritedCreateValidatorsModel extends CreateValidatorsOverrideBase
{
    public function rules(): array
    {
        return [['name']];
    }
}
