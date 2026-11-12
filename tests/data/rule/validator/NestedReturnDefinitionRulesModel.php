<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator;

use yii\base\Model;
use yii\validators\RequiredValidator;

/**
 * Model whose invalid validator types are returned by a closure, an anonymous model and another method, not by
 * `rules()`.
 */
final class NestedReturnDefinitionRulesModel extends Model
{
    public function anonymous(): Model
    {
        return new class extends Model {
            public function rules(): array
            {
                return [['name', ['class' => RequiredValidator::class]]];
            }
        };
    }

    /**
     * @return array<array-key, mixed>
     */
    public function extraRules(): array
    {
        return [['name', ['class' => RequiredValidator::class]]];
    }

    public function rules(): array
    {
        $extra = static function (): array {
            return [['name', ['class' => RequiredValidator::class]]];
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
