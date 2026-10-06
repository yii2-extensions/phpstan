<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

use yii\db\ActiveQuery;

/**
 * Stub query class whose `all()` override declares untyped array elements while its `one()` override names the model,
 * for query model derivation tests.
 */
final class DerivedModelUntypedAllQuery extends ActiveQuery
{
    /**
     * @return array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @return array|Invoice|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
