<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\yii;

use yii\db\ActiveRecord;

/**
 * @property bool $flag
 */
final class MyActiveRecord extends ActiveRecord
{
    /**
     * @return self<string, mixed>[]
     */
    public function getSiblings(): array
    {
        return $this->hasMany(self::class, ['link'])->where(['condition'])->all();
    }

    /**
     * @return self<string, mixed>|null
     */
    public function getMaster(): self|null
    {
        return $this->hasOne(self::class, ['link'])->one();
    }

    /**
     * @return self<string, mixed>[]
     */
    public function test(): array
    {
        return self::find()->all();
    }
}
