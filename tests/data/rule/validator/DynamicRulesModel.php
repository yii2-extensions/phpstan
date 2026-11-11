<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator;

use yii\base\Model;

/**
 * Model whose `rules()` returns merged, built or dynamically keyed lists, which native types don't prove constant.
 */
final class DynamicRulesModel extends Model
{
    /**
     * @return array<array-key, mixed>
     */
    public function rules(): array
    {
        if ($this->scenario === 'merged') {
            return [
                ...parent::rules(),
                ['name'],
            ];
        }

        if ($this->scenario === 'built') {
            $rules = [];

            foreach ($this->attributes() as $attribute) {
                $rules[] = [$attribute];
            }

            return $rules;
        }

        return [$this->scenario => ['name']];
    }
}
