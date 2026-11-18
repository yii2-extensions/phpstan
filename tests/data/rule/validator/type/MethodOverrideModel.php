<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator\type;

use yii\base\Model;

/**
 * Model whose `hasMethod()` makes a qualified class an inline method name, while the alias still takes priority.
 */
final class MethodOverrideModel extends Model
{
    public function hasMethod($name, $checkBehaviors = true): bool
    {
        return true;
    }

    /**
     * @return list<array<array-key, mixed>>
     */
    public function rules(): array
    {
        return [
            ['count', TypedValidator::class, 'limit' => 'all'],
            ['count', 'string', 'limit' => 'all'],
        ];
    }
}
