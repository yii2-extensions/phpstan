<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\stub;

use yii\db\ActiveQuery;

/**
 * @template T of Post
 * @extends ActiveQuery<T>
 */
final class PostQuery extends ActiveQuery
{
    /**
     * @return PostQuery<T>
     */
    public function published(): self
    {
        return $this->andWhere(['status' => 'published']);
    }
}
