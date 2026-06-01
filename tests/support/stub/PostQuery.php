<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

use yii\db\ActiveQuery;

/**
 * Stub ActiveQuery with a chainable `published()` method for {@see Post} query return type inference tests.
 *
 * @template T of Post
 * @extends ActiveQuery<T>
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
