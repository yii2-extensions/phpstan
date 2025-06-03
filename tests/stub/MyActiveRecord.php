<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\stub;

use yii\db\ActiveRecord;

/**
 * Stub ActiveRecord implementation for testing model relations and query scenarios.
 *
 * Provides a minimal ActiveRecord subclass for verifying relation methods, query building, and type inference in static
 * analysis and test environments.
 *
 * This stub is designed to simulate realistic ActiveRecord usage patterns without requiring a full database or
 * application context.
 *
 * The class defines example relation methods {@see getMaster()}, {@see getSiblings()} and a static query method
 * {@see test()} to demonstrate return type annotations, self-referencing relations, and integration with static
 * analysis tools.
 *
 * Key features.
 * - Designed for static analysis and test coverage validation.
 * - Example of property annotation for dynamic property support.
 * - No external dependencies or database required.
 * - Self-referencing relation methods for testing hasMany and hasOne patterns.
 * - Usage of PHPStan generics for precise return types.
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
