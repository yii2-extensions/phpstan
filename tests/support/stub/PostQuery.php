<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

use yii\db\ActiveQuery;

/**
 * Active Query class for performing database queries on {@see Post} models.
 *
 * Provides specialized query methods for {@see Post} entities, enabling type-safe database operations and query
 * building with method chaining.
 *
 * This class extends the base ActiveQuery functionality with Post-specific filtering and search capabilities.
 *
 * The query class maintains type safety through generic templates, ensuring that query results are typed as {@see Post}
 * objects and that chained method calls preserve the correct return types for static analysis tools.
 *
 * This implementation demonstrates custom ActiveQuery extension patterns in Yii2, providing domain-specific query
 * methods while maintaining compatibility with the base ActiveQuery interface and fluent query building.
 *
 * Key features:
 * - Generic type safety with template parameters for {@see Post} models.
 * - Method chaining with preserved return types for fluent interface.
 * - Post-specific query filtering methods.
 * - Type-safe query result handling.
 *
 * @template T of Post
 * @extends ActiveQuery<T>
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class PostQuery extends ActiveQuery
{
    /**
     * @phpstan-return PostQuery<T>
     */
    public function published(): self
    {
        return $this->andWhere(['status' => 'published']);
    }
}
