<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\type;

use yii\db\{ActiveQuery, ActiveRecord, Exception};
use yii2\extensions\phpstan\tests\stub\MyActiveRecord;
use yii2\extensions\phpstan\tests\stub\Post;

use function PHPStan\Testing\assertType;

/**
 * Test suite for dynamic method return types of {@see ActiveQuery} in Yii Active Record scenarios.
 *
 * Validates type inference and return types for {@see ActiveQuery} methods such as {@see ActiveQuery::asArray()},
 * {@see ActiveQuery::where()}, {@see ActiveQuery::orderBy()}, {@see ActiveQuery::limit()}, and result methods like
 * {@see ActiveQuery::all()} and {@see ActiveQuery::one()} in custom {@see ActiveRecord} implementations, including
 * chained query calls and array/object result scenarios.
 *
 * These tests ensure that PHPStan correctly infers the result types for query methods returning objects, arrays, and
 * query objects, and that type safety is preserved across chained query calls and various result scenarios.
 *
 * Test coverage.
 * - Array and object result validation for query methods.
 * - Chained query method return types ({@see ActiveQuery::where()}, {@see ActiveQuery::orderBy()},
 *   {@see ActiveQuery::limit()}, {@see ActiveQuery::asArray()}).
 * - Result types for {@see ActiveQuery::all()}, {@see ActiveQuery::one()} methods on queries.
 * - Type assertions for property and array access on query results.
 * - Type inference for {@see ActiveRecord::find()}, {@see ActiveRecord::findOne()}, {@see ActiveRecord::findAll()}, and
 *   {@see ActiveRecord::findBySql()} with and without chaining.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class ActiveQueryDynamicMethodReturnType
{
    public function testReturnActiveQueryWhenAsArrayWithVariableArgument(): void
    {
        $userPreference = $_POST['format'] ?? 'default';
        $useArrayFormat = ($userPreference === 'json');

        assertType(
            'yii\db\ActiveQuery<array{flag: bool}|yii2\extensions\phpstan\tests\stub\MyActiveRecord>',
            MyActiveRecord::find()->asArray($useArrayFormat),
        );
    }

    public function testReturnMyActiveRecordArrayQueryWhenAsArrayExplicitTrue(): void
    {
        assertType('yii\db\ActiveQuery<array{flag: bool}>', MyActiveRecord::find()->asArray(true));
    }

    public function testReturnMyActiveRecordArrayQueryWhenChainedWithAsArray(): void
    {
        $complexQuery = MyActiveRecord::find()
            ->where(['status' => 'active'])
            ->asArray()
            ->orderBy('created_at DESC')
            ->limit(10);

        assertType('yii\db\ActiveQuery<array{flag: bool}>', $complexQuery);
        assertType('array<int, array{flag: bool}>', $complexQuery->all());
    }

    public function testReturnMyActiveRecordArrayWhenArraysWithCondition(): void
    {
        $arrayRecords = MyActiveRecord::find()->asArray()->where(['flag' => true])->all();

        assertType('array<int, array{flag: bool}>', $arrayRecords);

        foreach ($arrayRecords as $record) {
            assertType('array{flag: bool}', $record);
            assertType('bool', $record['flag']);
        }
    }

    public function testReturnMyActiveRecordArrayWhenAsArrayWithAll(): void
    {
        $arrayQuery = MyActiveRecord::find()->asArray();

        assertType('yii\db\ActiveQuery<array{flag: bool}>', $arrayQuery);
        assertType('array<int, array{flag: bool}>', $arrayQuery->all());
    }

    public function testReturnMyActiveRecordArrayWhenFindAllWithCondition(): void
    {
        $modelRecords = MyActiveRecord::findAll('condition');

        assertType('array<yii2\extensions\phpstan\tests\stub\MyActiveRecord>', $modelRecords);

        foreach ($modelRecords as $record) {
            assertType('yii2\extensions\phpstan\tests\stub\MyActiveRecord', $record);
            assertType('bool', $record->flag);
        }
    }

    public function testReturnMyActiveRecordArrayWhenObjectsWithCondition(): void
    {
        $objectRecords = MyActiveRecord::find()->asArray(false)->where(['condition'])->all();

        assertType('array<int, yii2\extensions\phpstan\tests\stub\MyActiveRecord>', $objectRecords);

        foreach ($objectRecords as $record) {
            assertType('yii2\extensions\phpstan\tests\stub\MyActiveRecord', $record);
            assertType('bool', $record->flag);
            assertType('mixed', $record['flag']);
        }
    }

    /**
     * @throws Exception
     */
    public function testReturnMyActiveRecordOrNullWhenChainedWithOne(): void
    {
        $offsetProp = 'flag';
        $flag = false;

        assertType('\'flag\'', $offsetProp);
        assertType('false', $flag);

        $records = MyActiveRecord::find()->where(['flag' => true])->one();

        assertType('yii2\extensions\phpstan\tests\stub\MyActiveRecord|null', $records);

        if ($records !== null) {
            assertType('yii2\extensions\phpstan\tests\stub\MyActiveRecord', $records);
            assertType('mixed', $records[$offsetProp]);
            assertType('bool', $records->flag);
            assertType('bool', $records->save());
        }
    }

    public function testReturnMyActiveRecordOrNullWhenFindBySqlWithOne(): void
    {
        $queryFromSql = MyActiveRecord::findBySql('SELECT * FROM table');

        assertType('yii\db\ActiveQuery<yii2\extensions\phpstan\tests\stub\MyActiveRecord>', $queryFromSql);

        $recordOne = $queryFromSql->one();

        assertType('yii2\extensions\phpstan\tests\stub\MyActiveRecord|null', $recordOne);

        if ($recordOne !== null) {
            assertType('bool', $recordOne->flag);
            assertType('mixed', $recordOne['flag']);
        }
    }

    public function testReturnMyActiveRecordOrNullWhenFindOneWithCondition(): void
    {
        $records = MyActiveRecord::findOne(['condition']);

        assertType('yii2\extensions\phpstan\tests\stub\MyActiveRecord|null', $records);

        if ($records !== null) {
            assertType('bool', $records->flag);
            assertType('mixed', $records['flag']);
        }
    }

    public function testReturnMyActiveRecordQueryWhenAsArrayExplicitFalse(): void
    {
        assertType(
            'yii\db\ActiveQuery<yii2\extensions\phpstan\tests\stub\MyActiveRecord>',
            MyActiveRecord::find()->asArray(false),
        );
    }

    public function testReturnMyActiveRecordQueryWhenChainedWithConditions(): void
    {
        $query = MyActiveRecord::find();

        assertType(
            'yii\db\ActiveQuery<yii2\extensions\phpstan\tests\stub\MyActiveRecord>',
            $query,
        );
        assertType(
            'yii\db\ActiveQuery<yii2\extensions\phpstan\tests\stub\MyActiveRecord>',
            $query->where(['active' => 1]) -> andWhere(['status' => 'published']),
        );
    }

    public function testReturnUnionResultsWhenAsArrayWithVariableArgument(): void
    {
        $configValue = getenv('RESPONSE_FORMAT');
        $asArray = $configValue === 'array';

        $results = MyActiveRecord::find()->asArray($asArray)->all();

        assertType('array<int, array{flag: bool}|yii2\extensions\phpstan\tests\stub\MyActiveRecord>', $results);

        foreach ($results as $result) {
            assertType('array{flag: bool}|yii2\extensions\phpstan\tests\stub\MyActiveRecord', $result);
        }
    }

    public function testReturnActiveQueryWhenCustomQuerySubclass(): void
    {
        $customQuery = Post::find();

        assertType(
            'yii2\extensions\phpstan\tests\stub\PostQuery<yii2\extensions\phpstan\tests\stub\Post>',
            $customQuery,
        );
        assertType(
            'yii2\extensions\phpstan\tests\stub\PostQuery<array{title: string, content: string}>',
            $customQuery->asArray(),
        );
        assertType(
            'yii2\extensions\phpstan\tests\stub\Post|null',
            $customQuery->one(),
        );
        assertType(
            'array<int, yii2\extensions\phpstan\tests\stub\Post>',
            $customQuery->all(),
        );
        assertType(
            'yii2\extensions\phpstan\tests\stub\PostQuery<yii2\extensions\phpstan\tests\stub\Post>',
            $customQuery->published(),
        );
    }
}
