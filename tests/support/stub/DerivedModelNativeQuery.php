<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

use yii\db\ActiveQuery;

/**
 * Stub query class whose `one()` override declares the model natively, for query model derivation tests.
 */
final class DerivedModelNativeQuery extends ActiveQuery
{
    public function one($db = null): Post|null
    {
        return null;
    }
}
