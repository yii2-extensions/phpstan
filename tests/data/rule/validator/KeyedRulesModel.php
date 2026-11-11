<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator;

use yii\base\Model;

/**
 * Model whose `rules()` list uses explicit, negative, normalized and duplicate keys; every resulting rule is incomplete.
 */
final class KeyedRulesModel extends Model
{
    /**
     * @return array<array-key, mixed>
     */
    public function rules(): array
    {
        return [
            // PHP keeps the last value of a duplicate key: the incomplete rule must come last
            'named' => [
                'name',
                'required',
            ],
            'named' => ['named'],
            -3 => ['negative'],
            // since PHP 8.3 the implicit key follows the negative key, and `-2` replaces it
            [
                'name',
                'required',
            ],
            -2 => [
                null,
                'required',
            ],
            // PHP normalizes `'7'` to `7`, so the later incomplete rule replaces the complete one
            7 => [
                'name',
                'trim',
            ],
            '7' => ['normalized'],
        ];
    }
}
