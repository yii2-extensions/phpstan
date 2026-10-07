<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

use yii\db\ActiveQuery;

/**
 * Stub query class whose `one()` override declares the model and `null` only, for query model derivation tests.
 */
final class DerivedModelDocQuery extends ActiveQuery
{
    /**
     * @return Post|null
     */
    public function one($db = null)
    {
        return null;
    }
}
