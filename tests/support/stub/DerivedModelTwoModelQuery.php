<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

use yii\db\ActiveQuery;

/**
 * Stub query class whose `one()` override names two models, for query model derivation tests.
 */
final class DerivedModelTwoModelQuery extends ActiveQuery
{
    /**
     * @return array|Invoice|Shipment|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
