<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

use yii\db\ActiveRecord;

/**
 * Stub ActiveRecord model with relation and query methods for dynamic return type inference tests.
 *
 * @property bool $flag
 */
final class MyActiveRecord extends ActiveRecord
{
    /**
     * @return self<string, mixed>|null
     */
    public function getMaster(): self|null
    {
        return $this->hasOne(self::class, ['id' => 'id'])->one();
    }

    /**
     * @return self<string, mixed>[]
     */
    public function getSiblings(): array
    {
        return $this->hasMany(self::class, ['id' => 'id'])->where(['condition'])->all();
    }

    /**
     * @return self<string, mixed>[]
     */
    public function test(): array
    {
        return self::find()->all();
    }
}
