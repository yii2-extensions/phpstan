<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\type;

use yii\db\ActiveQuery;
use yii2\extensions\phpstan\tests\support\stub\{
    CommentQuery,
    CreditInvoiceQuery,
    DerivedModelBaseRecordQuery,
    DerivedModelDocQuery,
    DerivedModelNativeQuery,
    DerivedModelNonRecordQuery,
    DerivedModelSplitQuery,
    DerivedModelTwoModelQuery,
    InvoiceQuery,
    ShipmentQuery,
};

use function is_array;
use function PHPStan\Testing\assertType;

/**
 * Type assertion fixture for the query classes from which no model is derived or whose `one()` and `all()` overrides
 * are already tight, and for union receivers.
 */
final class ActiveQueryDerivedModelEdgeType
{
    public function testKeepNativeTypeWhenOverridesNameNoSingleModel(
        DerivedModelTwoModelQuery $twoModelQuery,
        DerivedModelSplitQuery $splitQuery,
        DerivedModelBaseRecordQuery $baseRecordQuery,
        DerivedModelNonRecordQuery $nonRecordQuery,
    ): void {
        assertType(
            'array|yii2\extensions\phpstan\tests\support\stub\Invoice|yii2\extensions\phpstan\tests\support\stub\Shipment|null',
            $twoModelQuery->one(),
        );
        assertType(
            'bool',
            is_array($twoModelQuery->all()[0] ?? null),
        );
        assertType(
            'yii\db\ActiveQuery<array<string, mixed>>',
            $twoModelQuery->asArray(),
        );
        assertType(
            'array|yii2\extensions\phpstan\tests\support\stub\Invoice|null',
            $splitQuery->one(),
        );
        assertType(
            'array<yii2\extensions\phpstan\tests\support\stub\Shipment>',
            $splitQuery->all(),
        );
        assertType(
            'array|yii\db\ActiveRecord|null',
            $baseRecordQuery->one(),
        );
        assertType(
            'array<yii\db\ActiveRecord>',
            $baseRecordQuery->all(),
        );
        assertType(
            'array|yii\base\Model|null',
            $nonRecordQuery->one(),
        );
        assertType(
            'yii\db\ActiveQuery<array<string, mixed>>',
            $nonRecordQuery->asArray(),
        );
    }

    public function testKeepNativeTypeWhenReceiverIsUnion(InvoiceQuery|ShipmentQuery $query): void
    {
        assertType('array|yii\db\ActiveRecord|null', $query->one());
    }

    public function testKeepOwnAnswerOrBetterWhenOverridesAreTight(
        DerivedModelNativeQuery $nativeQuery,
        DerivedModelDocQuery $docQuery,
    ): void {
        assertType('yii2\extensions\phpstan\tests\support\stub\Post|null', $nativeQuery->one());
        assertType('array<yii2\extensions\phpstan\tests\support\stub\Post>', $nativeQuery->all());
        assertType('array{title: string, content: string}|null', $nativeQuery->asArray()->one());
        assertType('yii2\extensions\phpstan\tests\support\stub\Post|null', $docQuery->one());
        assertType('array<yii2\extensions\phpstan\tests\support\stub\Post>', $docQuery->where(['id' => 1])->all());

        foreach ($docQuery->each() as $post) {
            assertType('yii2\extensions\phpstan\tests\support\stub\Post', $post);
        }
    }

    public function testKeepRawActiveQueryWhenRelationUnionCollapses(ActiveQuery|CommentQuery $query): void
    {
        assertType('yii\db\ActiveQuery', $query);
        assertType('bool', is_array($query->one()));
    }

    public function testReturnInvoiceWhenUnionCollapsesToSingleQueryClass(InvoiceQuery|CreditInvoiceQuery $query): void
    {
        assertType('yii2\extensions\phpstan\tests\support\stub\Invoice|null', $query->one());
    }
}
