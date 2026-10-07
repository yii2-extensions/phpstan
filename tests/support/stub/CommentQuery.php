<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

use yii\db\ActiveQuery;

/**
 * Stub non-generic ActiveQuery bound to {@see Comment} with a chainable `approved()` method for query return type
 * inference tests.
 *
 * @extends ActiveQuery<Comment>
 */
final class CommentQuery extends ActiveQuery
{
    public function approved(): static
    {
        return $this->andWhere(['approved' => 1]);
    }
}
