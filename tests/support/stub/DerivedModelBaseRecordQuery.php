<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

use yii\db\{ActiveQuery, ActiveRecord};

/**
 * Stub query class whose `one()` and `all()` overrides name no model, only the base Active Record class, for query
 * model derivation tests.
 */
final class DerivedModelBaseRecordQuery extends ActiveQuery
{
    /**
     * @return ActiveRecord[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @return ActiveRecord|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
