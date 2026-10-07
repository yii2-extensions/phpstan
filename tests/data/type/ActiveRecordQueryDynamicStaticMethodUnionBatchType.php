<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\type;

use yii2\extensions\phpstan\tests\support\stub\Invoice;

use function PHPStan\Testing\assertType;

/**
 * Type assertion fixture for the inferred query of {@see Invoice::find()} with the union ordering methods and the
 * generic {@see \yii\db\BatchQueryResult} that only Yii 22 declares.
 */
final class ActiveRecordQueryDynamicStaticMethodUnionBatchType
{
    public function testKeepInferredQueryWhenUnionOrderingMethodIsCalled(): void
    {
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->unionOrderBy('id'),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->addUnionOrderBy('id'),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->unionLimit(10),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->unionOffset(10),
        );
    }

    public function testReturnInvoiceBatchQueryResultWhenBatchOrEachIsCalled(): void
    {
        assertType(
            'yii\db\BatchQueryResult<int, array<yii2\extensions\phpstan\tests\support\stub\Invoice>>',
            Invoice::find()->batch(),
        );
        assertType(
            'yii\db\BatchQueryResult<(int|string), yii2\extensions\phpstan\tests\support\stub\Invoice>',
            Invoice::find()->each(),
        );
    }
}
