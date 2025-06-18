<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\stub;

use yii\db\ActiveRecord;

/**
 * Custom ActiveRecord implementation for testing dynamic return types and relations.
 *
 * Provides a minimal ActiveRecord subclass with a boolean property and methods for testing Yii Active Record dynamic
 * return types, including single and multiple relation retrieval and query result scenarios.
 *
 * This class is used in type inference and static analysis tests to validate PHPStan's ability to correctly infer
 * return types for ActiveRecord queries, relations, and chained method calls.
 *
 * @property bool $flag
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class MyActiveRecord extends ActiveRecord
{
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
    public function getSiblings(): array
    {
        return $this->hasMany(self::class, ['link'])->where(['condition'])->all();
    }

    /**
     * @return self<string, mixed>[]
     */
    public function test(): array
    {
        return self::find()->all();
    }
}
