<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator;

use yii2\extensions\phpstan\tests\support\validator\GetValidatorsOverrideBase;

/**
 * Model whose ancestor overrides `getValidators()`.
 */
final class InheritedGetValidatorsModel extends GetValidatorsOverrideBase
{
    public function rules(): array
    {
        return [['name']];
    }
}
