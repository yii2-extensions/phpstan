<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

use yii\base\Model;
use yii\db\ActiveQuery;

/**
 * Stub query class whose `one()` override names a class that is not an Active Record, for query model derivation tests.
 */
final class DerivedModelNonRecordQuery extends ActiveQuery
{
    /**
     * @return array|Model|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
