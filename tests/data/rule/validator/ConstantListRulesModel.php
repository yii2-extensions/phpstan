<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator;

use yii\base\Model;

/**
 * Model whose `rules()` returns a list built away from the return, which native types prove constant.
 */
final class ConstantListRulesModel extends Model
{
    private const array RULES = [
        [
            'name',
            'required',
        ],
        ['email'],
    ];

    public function rules(): array
    {
        $rules = self::RULES;

        return $rules;
    }
}
