<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

use yii\db\ActiveQuery;

/**
 * Stub query class with non-null `one()` and list `all()` declarations for model derivation tests.
 */
final class DerivedModelNativeQuery extends ActiveQuery
{
    /**
     * @return list<Post>
     */
    public function all($db = null): array
    {
        return [];
    }

    public function one($db = null): Post
    {
        return new Post();
    }
}
