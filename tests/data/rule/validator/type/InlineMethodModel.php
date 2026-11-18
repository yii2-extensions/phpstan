<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator\type;

use yii\base\Model;
use yii\validators\InlineValidator;

/**
 * Model whose closure rules get the closure as `method`, and whose qualified `InlineValidator` rules write `method`.
 */
final class InlineMethodModel extends Model
{
    /**
     * @return list<array<array-key, mixed>>
     */
    public function rules(): array
    {
        return [
            ['count', static fn(): bool => true],
            ['count', static fn(): bool => true, 'method' => []],
            ['count', InlineValidator::class, 'method' => 'check'],
            ['count', InlineValidator::class, 'method' => '5'],
        ];
    }
}
