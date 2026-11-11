<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator;

use yii\base\Model;

/**
 * Model whose incomplete rules are returned by a closure, an anonymous model and another method, not by `rules()`.
 */
final class NestedReturnRulesModel extends Model
{
    public function anonymous(): Model
    {
        return new class extends Model {
            public function rules(): array
            {
                return [['name']];
            }
        };
    }
    /**
     * @return array<array-key, mixed>
     */
    public function extraRules(): array
    {
        return [['name']];
    }

    public function rules(): array
    {
        $extra = static function (): array {
            return [['name']];
        };

        return [
            [
                'name',
                'required',
                'when' => $extra,
            ],
        ];
    }
}
