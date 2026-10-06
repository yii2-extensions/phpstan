<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

/**
 * Stub non-generic query class deriving from the Gii-generated {@see InvoiceQuery} without `one()` or `all()`
 * overrides of its own, with a `refunded()` scope returning `self`, for query model derivation tests.
 */
class CreditInvoiceQuery extends InvoiceQuery
{
    public function refunded(): self
    {
        return $this->andWhere(['refunded' => 1]);
    }
}
