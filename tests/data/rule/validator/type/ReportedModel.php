<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\validator\type;

use yii\base\Model;

/**
 * Model whose rules write option values that the property or setter of the created validator never accepts.
 */
final class ReportedModel extends Model
{
    /**
     * @return list<array<array-key, mixed>>
     */
    public function rules(): array
    {
        return [
            ['count', TypedValidator::class, 'limit' => 'all'],
            ['count', TypedValidator::class, 'limit' => null],
            ['count', TypedValidator::class, 'label' => ['short']],
            ['count', TypedValidator::class, 'tags' => 'news'],
            ['count', TypedValidator::class, 'list' => 'a', 'limit' => ''],
            ['count', 'string', '__class' => TypedValidator::class, 'limit' => 'all'],
            ['count', 'app\validators\Limited', 'limit' => [1]],
            ['count', 'string', 'limit' => 'all'],
            ['count', TypedValidator::class, 'limit' => 'all', 'on' => 'inactive'],
            ['count', TypedValidator::class, 'unknown' => 1, 'limit' => 'all'],
        ];
    }
}
