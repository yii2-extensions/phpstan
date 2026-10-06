<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

use yii\db\ActiveQuery;

/**
 * Stub non-generic ActiveQuery without `one()` or `all()` overrides, with scopes returning `static`, `self`, `$this`,
 * and no declared type, for query model inference tests.
 */
final class ShipmentQuery extends ActiveQuery
{
    public function delivered(): self
    {
        return $this->andWhere(['status' => 'delivered']);
    }

    public function pending(): static
    {
        return $this->andWhere(['status' => 'pending']);
    }

    public function returned()
    {
        return $this->andWhere(['status' => 'returned']);
    }

    /**
     * @return $this
     */
    public function shipped()
    {
        return $this->andWhere(['status' => 'shipped']);
    }
}
