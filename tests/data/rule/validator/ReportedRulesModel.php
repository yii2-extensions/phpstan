<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator;

use yii\base\Model;

/**
 * Model whose `rules()` returns only rules without a non-null key `0` or `1`; its own return type accepts them.
 */
final class ReportedRulesModel extends Model
{
    private const array MISSING_TYPE = ['constant'];

    /**
     * @return array<array-key, mixed>
     */
    public function rules(): array
    {
        return [
            ['name'],
            [['name', 'email'], 'on' => 'create'],
            ['name', null],
            [null, 'required'],
            ['name', 'validator' => 'required'],
            ['attributes' => 'name', 'required'],
            [],
            [null],
            [1 => 'required'],
            ['1' => 'required', 'message' => 'Normalized.'],
            // PHP keeps the last value of a duplicate key: `null` replaces the type
            ['name', 'required', 1 => null],
            self::MISSING_TYPE,
            [
                'multiLine',
                'message' => 'Required.',
            ],
        ];
    }
}
