<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator\type;

use yii\base\Model;
use yii\validators\StringValidator;

/**
 * Model whose rules write option values that PHP's weak type coercion converts, or options without a native type.
 */
final class ValidModel extends Model
{
    /**
     * @return list<array<array-key, mixed>>
     */
    public function rules(): array
    {
        return [
            ['count', TypedValidator::class, 'limit' => '20'],
            ['count', TypedValidator::class, 'limit' => 5.0],
            ['count', TypedValidator::class, 'limit' => true],
            ['count', TypedValidator::class, 'label' => 5],
            ['count', TypedValidator::class, 'strict' => 'yes'],
            ['count', TypedValidator::class, 'tags' => ['news']],
            ['count', TypedValidator::class, 'loose' => 'any'],
            ['count', TypedValidator::class, 'handler' => 'strlen'],
            ['count', TypedValidator::class, 'documented' => 'text'],
            ['count', TypedValidator::class, 'attributes' => 5],
            ['count', 'string', 'limit' => '3', 'max' => 'abc'],
            ['count', StringValidator::class, 'limit' => '3'],
            ['count', 'app\validators\Limited', 'limit' => 3],
            ['count', static fn(): bool => true, 'params' => 'text', 'current' => 'text'],
        ];
    }
}
