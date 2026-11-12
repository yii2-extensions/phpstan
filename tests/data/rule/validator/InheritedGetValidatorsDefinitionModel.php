<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator;

use yii\validators\RequiredValidator;
use yii2\extensions\phpstan\tests\support\validator\GetValidatorsOverrideBase;

/**
 * Model whose ancestor overrides `getValidators()`, returning an invalid validator type.
 */
final class InheritedGetValidatorsDefinitionModel extends GetValidatorsOverrideBase
{
    public function rules(): array
    {
        return [['name', ['class' => RequiredValidator::class]]];
    }
}
