<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\type;

use yii2\extensions\phpstan\tests\support\stub\{CreditInvoiceQuery, Invoice, InvoiceQuery, ShipmentQuery};

use function is_array;
use function PHPStan\Testing\assertType;

/**
 * Type assertion fixture for the model derived from the `one()` and `all()` overrides of a non-generic query class
 * reached without a call site that names the model.
 *
 * Covers the Gii-generated {@see InvoiceQuery} and the {@see CreditInvoiceQuery} deriving from it as a typed parameter,
 * a typed property, a returned value, and the result of a scope returning `self`, through every row-dependent
 * {@see \yii\db\ActiveQuery} method, and a bare query class that keeps PHPStan's own answer, whose rows may still be
 * arrays.
 */
final class ActiveQueryDerivedModelReturnType
{
    private CreditInvoiceQuery $creditInvoiceQuery;
    private InvoiceQuery $invoiceQuery;

    public function __construct()
    {
        $this->invoiceQuery = Invoice::find();
        $this->creditInvoiceQuery = new CreditInvoiceQuery(Invoice::class);
    }

    public function testKeepNativeTypeWhenBareQueryClassIsTypedParameter(ShipmentQuery $query): void
    {
        assertType(
            'bool',
            is_array($query->one()),
        );
        assertType(
            'bool',
            is_array($query->pending()->all()[0] ?? null),
        );
        assertType(
            'yii\db\ActiveQuery<array<string, mixed>>',
            $query->asArray(),
        );
    }

    public function testKeepQueryClassWhenFluentMethodOrScopeIsCalled(InvoiceQuery $query): void
    {
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery',
            $query->where(['id' => 1]),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery',
            $query->paid(),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery',
            $query->asArray(false),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\InvoiceQuery',
            Invoice::find()->paid(),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\CreditInvoiceQuery',
            $this->creditInvoiceQuery->refunded()->orderBy('id'),
        );
    }

    public function testReturnInvoiceWhenDerivedQueryIsTypedParameter(CreditInvoiceQuery $query): void
    {
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\Invoice|null',
            $query->one(),
        );
        assertType(
            'array<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            $query->all(),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\Invoice|null',
            $query->where(['id' => 1])->refunded()->limit(1)->one(),
        );
        assertType(
            'array{id: int, number: string, comments?: array<array<string, mixed>>}|null',
            $query->refunded()->asArray()->one(),
        );
        assertType(
            'array<array{id: int, number: string, comments?: array<array<string, mixed>>}>',
            $query->asArray()->all(),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\Invoice|null',
            $query->asArray(false)->one(),
        );

        foreach ($query->batch() as $invoices) {
            assertType(
                'array<yii2\extensions\phpstan\tests\support\stub\Invoice>',
                $invoices,
            );
        }

        foreach ($query->refunded()->each() as $invoice) {
            assertType(
                'yii2\extensions\phpstan\tests\support\stub\Invoice',
                $invoice,
            );
        }
    }

    public function testReturnInvoiceWhenQueryIsReturnedValue(): void
    {
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\Invoice|null',
            $this->invoiceQuery()->one(),
        );
        assertType(
            'array<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            $this->invoiceQuery()->all(),
        );
        assertType(
            'array{id: int, number: string, comments?: array<array<string, mixed>>}|null',
            $this->invoiceQuery()->asArray()->one(),
        );
        assertType(
            'array<array{id: int, number: string, comments?: array<array<string, mixed>>}>',
            $this->invoiceQuery()->where(['id' => 1])->asArray()->all(),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\Invoice|null',
            $this->invoiceQuery()->asArray(false)->one(),
        );

        foreach ($this->invoiceQuery()->batch(10) as $invoices) {
            assertType(
                'array<yii2\extensions\phpstan\tests\support\stub\Invoice>',
                $invoices,
            );
        }

        foreach ($this->invoiceQuery()->each() as $invoice) {
            assertType(
                'yii2\extensions\phpstan\tests\support\stub\Invoice',
                $invoice,
            );
        }
    }

    public function testReturnInvoiceWhenQueryIsScopeResult(InvoiceQuery $query): void
    {
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\Invoice|null',
            $query->paid()->one(),
        );
        assertType(
            'array<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            $query->paid()->all(),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\Invoice|null',
            Invoice::find()->paid()->one(),
        );
        assertType(
            'array<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            $this->creditInvoiceQuery->refunded()->paid()->where(['id' => 1])->all(),
        );
        assertType(
            'array{id: int, number: string, comments?: array<array<string, mixed>>}|null',
            $query->paid()->asArray()->one(),
        );
        assertType(
            'array<array{id: int, number: string, comments?: array<array<string, mixed>>}>',
            $query->paid()->asArray()->all(),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\Invoice|null',
            $query->paid()->asArray(false)->one(),
        );

        foreach ($query->paid()->batch() as $invoices) {
            assertType(
                'array<yii2\extensions\phpstan\tests\support\stub\Invoice>',
                $invoices,
            );
        }

        foreach (Invoice::find()->paid()->each() as $invoice) {
            assertType(
                'yii2\extensions\phpstan\tests\support\stub\Invoice',
                $invoice,
            );
        }
    }

    public function testReturnInvoiceWhenQueryIsTypedParameter(InvoiceQuery $query): void
    {
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\Invoice|null',
            $query->one(),
        );
        assertType(
            'array<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            $query->all(),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\Invoice|null',
            $query->where(['id' => 1])->andWhere(['number' => 'A'])->one(),
        );
        assertType(
            'yii\db\ActiveQuery<array{id: int, number: string, comments?: array<array<string, mixed>>}>',
            $query->asArray(),
        );
        assertType(
            'array{id: int, number: string, comments?: array<array<string, mixed>>}|null',
            $query->asArray()->one(),
        );
        assertType(
            'array<array{id: int, number: string, comments?: array<array<string, mixed>>}>',
            $query->orderBy('id')->asArray()->all(),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\Invoice|null',
            $query->asArray(false)->one(),
        );
        assertType(
            'array{id: int, number: string, comments?: array<array<string, mixed>>}|yii2\extensions\phpstan\tests\support\stub\Invoice|null',
            $query->asArray((bool) $query->count())->one(),
        );

        foreach ($query->batch() as $invoices) {
            assertType(
                'array<yii2\extensions\phpstan\tests\support\stub\Invoice>',
                $invoices,
            );
        }

        foreach ($query->each() as $invoice) {
            assertType(
                'yii2\extensions\phpstan\tests\support\stub\Invoice',
                $invoice,
            );
        }
    }

    public function testReturnInvoiceWhenQueryIsTypedProperty(): void
    {
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\Invoice|null',
            $this->invoiceQuery->one(),
        );
        assertType(
            'array<yii2\extensions\phpstan\tests\support\stub\Invoice>',
            $this->invoiceQuery->all(),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\Invoice|null',
            $this->creditInvoiceQuery->one(),
        );
        assertType(
            'array{id: int, number: string, comments?: array<array<string, mixed>>}|null',
            $this->invoiceQuery->asArray()->one(),
        );
        assertType(
            'array<array{id: int, number: string, comments?: array<array<string, mixed>>}>',
            $this->creditInvoiceQuery->asArray()->all(),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\Invoice|null',
            $this->invoiceQuery->asArray(false)->where(['id' => 1])->one(),
        );

        foreach ($this->invoiceQuery->batch() as $invoices) {
            assertType(
                'array<yii2\extensions\phpstan\tests\support\stub\Invoice>',
                $invoices,
            );
        }

        foreach ($this->creditInvoiceQuery->each() as $invoice) {
            assertType(
                'yii2\extensions\phpstan\tests\support\stub\Invoice',
                $invoice,
            );
        }
    }

    private function invoiceQuery(): InvoiceQuery
    {
        return $this->invoiceQuery;
    }
}
