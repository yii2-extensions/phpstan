<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator;

use yii\validators\RequiredValidator;
use yii2\extensions\phpstan\tests\support\validator\CreateValidatorsOverrideBase;

/**
 * Model whose ancestor overrides `createValidators()`, returning an invalid validator type.
 */
final class InheritedCreateValidatorsDefinitionModel extends CreateValidatorsOverrideBase
{
    public function rules(): array
    {
        return [['name', ['class' => RequiredValidator::class]]];
    }
}
