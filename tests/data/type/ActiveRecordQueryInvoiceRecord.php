<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\type;

use yii2\extensions\phpstan\tests\support\stub\Invoice;

use function PHPStan\Testing\assertType;

/**
 * Subclass of the Gii-generated {@see Invoice} model calling its query methods through `self`, `static`, and `parent`.
 */
final class ActiveRecordQueryInvoiceRecord extends Invoice
{
    public function testReturnInvoiceWhenSelfScopeCalledInsideModel(): void
    {
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\Invoice|null',
            self::find()->paid()->one(),
        );
        assertType(
            'array<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            self::find()->paid()->all(),
        );
    }

    public function testReturnStaticModelWhenFindCalledInsideModel(): void
    {
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery&yii\db\ActiveQuery<static(yii2\extensions\phpstan\tests\data\type\ActiveRecordQueryInvoiceRecord)>',
            self::find(),
        );
        assertType(
            'static(yii2\extensions\phpstan\tests\data\type\ActiveRecordQueryInvoiceRecord)|null',
            self::find()->one(),
        );
        assertType(
            'array<static(yii2\extensions\phpstan\tests\data\type\ActiveRecordQueryInvoiceRecord)>',
            self::find()->where(['id' => 1])->all(),
        );
        assertType(
            'static(yii2\extensions\phpstan\tests\data\type\ActiveRecordQueryInvoiceRecord)|null',
            parent::find()->one(),
        );
        assertType(
            'static(yii2\extensions\phpstan\tests\data\type\ActiveRecordQueryInvoiceRecord)|null',
            $this::find()->one(),
        );
        assertType(
            'array{id: int, number: string, comments?: array<array<string, mixed>>}|null',
            self::find()->asArray()->one(),
        );

        foreach (parent::find()->each() as $invoice) {
            assertType('static(yii2\extensions\phpstan\tests\data\type\ActiveRecordQueryInvoiceRecord)', $invoice);
        }
    }

    public static function testReturnStaticModelWhenFindCalledInStaticMethod(): void
    {
        assertType(
            'static(yii2\extensions\phpstan\tests\data\type\ActiveRecordQueryInvoiceRecord)|null',
            self::find()->one(),
        );
        assertType(
            'static(yii2\extensions\phpstan\tests\data\type\ActiveRecordQueryInvoiceRecord)|null',
            self::find()->one(),
        );
    }
}
