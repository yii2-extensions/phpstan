<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator\type;

use yii\base\Model;

/**
 * Model whose rules set `clientScript`, which Yii `22` declares with a native type and Yii `2.0.55` doesn't declare.
 */
final class ClientScriptModel extends Model
{
    /**
     * @return list<array<array-key, mixed>>
     */
    public function rules(): array
    {
        return [
            ['name', 'trim', 'clientScript' => 'script'],
            ['count', 'integer', 'clientScript' => 5],
            ['name', 'trim', 'clientScript' => null],
        ];
    }
}
