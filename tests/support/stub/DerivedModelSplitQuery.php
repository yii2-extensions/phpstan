<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

use yii\db\ActiveQuery;

/**
 * Stub query class whose `one()` and `all()` overrides name different models, for query model derivation tests.
 */
final class DerivedModelSplitQuery extends ActiveQuery
{
    /**
     * @return Shipment[]
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @return Invoice|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
