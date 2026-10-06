<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

use yii\db\ActiveQuery;

/**
 * Stub query class whose `one()` override also declares `false` while its `all()` override names the model, for query
 * model derivation tests.
 */
final class DerivedModelFalseOneQuery extends ActiveQuery
{
    /**
     * @return array|Invoice[]
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @return array|false|Invoice|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
