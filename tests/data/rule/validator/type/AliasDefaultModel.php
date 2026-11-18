<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator\type;

use yii\base\Model;

/**
 * Model whose `datetime` rules keep, override or replace the `type` default of the alias.
 */
final class AliasDefaultModel extends Model
{
    /**
     * @return list<array<array-key, mixed>>
     */
    public function rules(): array
    {
        return [
            ['at', 'datetime'],
            ['at', 'datetime', 'type' => 'local'],
            ['at', 'datetime', 'type' => 1],
        ];
    }
}
