<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

use yii\db\ActiveQuery;

/**
 * Stub query class whose `all()` override names the model and also declares `null`, for query model derivation tests.
 */
final class DerivedModelNullableAllQuery extends ActiveQuery
{
    /**
     * @return Invoice[]|null
     */
    public function all($db = null)
    {
        return parent::all($db);
    }
}
