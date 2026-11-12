<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator;

use yii\base\Model;
use yii\validators\RequiredValidator;

/**
 * Model whose `rules()` returns a variable that native types prove to be one constant list.
 */
final class ConstantInvalidDefinitionListModel extends Model
{
    public function rules(): array
    {
        $rules = [
            ['name', 'required'],
            ['name', ['class' => RequiredValidator::class]],
        ];

        return $rules;
    }
}
