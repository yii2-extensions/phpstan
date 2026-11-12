<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator;

use ArrayObject;
use yii\base\Model;
use yii\validators\RequiredValidator;

/**
 * Model that overrides `getValidators()`, so that Yii's use of the invalid validator type isn't proven.
 */
final class GetValidatorsDefinitionOverrideModel extends Model
{
    public function getValidators(): ArrayObject
    {
        return new ArrayObject();
    }

    public function rules(): array
    {
        return [['name', ['class' => RequiredValidator::class]]];
    }
}
