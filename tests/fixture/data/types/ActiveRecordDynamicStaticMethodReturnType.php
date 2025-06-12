<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\fixture\data\types;

use yii\db\{ActiveQuery, ActiveRecord};
use yii2\extensions\phpstan\tests\stub\{Category, MyActiveRecord, User};

use function PHPStan\Testing\assertType;

/**
 * Test suite for dynamic static method return types of {@see ActiveRecord} in Yii Active Record scenarios.
 *
 * Validates type inference and return types for static {@see ActiveRecord} methods such as {@see ActiveRecord::find()},
 * {@see ActiveRecord::findOne()}, {@see ActiveRecord::findAll()}, and {@see ActiveRecord::findBySql()} in custom
 * {@see ActiveRecord} implementations, including chained query calls and array/object result scenarios.
 *
 * These tests ensure that PHPStan correctly infers the result types for static query methods returning objects, arrays,
 * and query objects, and that type safety is preserved across chained query calls and various result scenarios.
 *
 * Test coverage.
 * - Array and object result validation for static query methods.
 * - Chained query method return types ({@see ActiveQuery::where()}, {@see ActiveQuery::orderBy()},
 *   {@see ActiveQuery::limit()}, {@see ActiveQuery::asArray()}).
 * - Result types for {@see ActiveQuery::all()}, {@see ActiveQuery::one()} methods on static queries.
 * - Type assertions for property and array access on static query results.
 * - Type inference for {@see ActiveRecord::find()}, {@see ActiveRecord::findOne()}, {@see ActiveRecord::findAll()}, and
 *   {@see ActiveRecord::findBySql()} with and without.
 *   chaining.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class ActiveRecordDynamicStaticMethodReturnType
{
    public function testReturnCategoryArrayQueryWhenFindBySqlWithAsArray(): void
    {
        $query = Category::findBySql('SELECT * FROM categories')->asArray();

        assertType('yii\db\ActiveQuery<array{id: int, name: string, parent_id: int|null}>', $query);
    }

    public function testReturnCategoryQueryWhenFindOnCategory(): void
    {
        $categoryQuery = Category::find();

        assertType('yii\db\ActiveQuery<yii2\extensions\phpstan\tests\stub\Category>', $categoryQuery);
    }

    public function testReturnMyActiveRecordArrayQueryWhenFindAsArray(): void
    {
        $query = MyActiveRecord::find()->asArray();

        assertType('yii\db\ActiveQuery<array{flag: bool}>', $query);
    }

    public function testReturnMyActiveRecordArrayWhenFindAllWithIds(): void
    {
        $records = MyActiveRecord::findAll([1, 2, 3]);

        assertType('array<yii2\extensions\phpstan\tests\stub\MyActiveRecord>', $records);
    }

    public function testReturnMyActiveRecordOrNullWhenFindOneAfterChaining(): void
    {
        $record = MyActiveRecord::find()->where(['status' => 'published'])->one();

        assertType('yii2\extensions\phpstan\tests\stub\MyActiveRecord|null', $record);
    }

    public function testReturnMyActiveRecordOrNullWhenFindOneById(): void
    {
        $record = MyActiveRecord::findOne(1);

        assertType('yii2\extensions\phpstan\tests\stub\MyActiveRecord|null', $record);
    }

    public function testReturnMyActiveRecordQueryWhenFind(): void
    {
        $query = MyActiveRecord::find();

        assertType('yii\db\ActiveQuery<yii2\extensions\phpstan\tests\stub\MyActiveRecord>', $query);
    }

    public function testReturnMyActiveRecordQueryWhenFindBySqlWithParameters(): void
    {
        $query = MyActiveRecord::findBySql('SELECT * FROM my_table WHERE id = :id', [':id' => 1]);

        assertType('yii\db\ActiveQuery<yii2\extensions\phpstan\tests\stub\MyActiveRecord>', $query);
    }

    public function testReturnMyActiveRecordQueryWhenFindWithChaining(): void
    {
        $query = MyActiveRecord::find()->where(['status' => 'active'])->orderBy('created_at DESC');

        assertType('yii\db\ActiveQuery<yii2\extensions\phpstan\tests\stub\MyActiveRecord>', $query);
    }

    public function testReturnMyActiveRecordWhenInstantiating(): void
    {
        $model = new MyActiveRecord();

        assertType('yii2\extensions\phpstan\tests\stub\MyActiveRecord', $model);
    }

    public function testReturnUserArrayQueryWhenFindAsArray(): void
    {
        $userQuery = User::find()->asArray();

        assertType('yii\db\ActiveQuery<array{id: int, name: string, email: string}>', $userQuery);
    }

    public function testReturnUserArrayWhenFindAllAfterChaining(): void
    {
        $records = User::find()->where(['active' => 1])->orderBy('name ASC')->all();

        assertType('array<int, yii2\extensions\phpstan\tests\stub\User>', $records);
    }

    public function testReturnUserArrayWhenFindAllWithCondition(): void
    {
        $userRecords = User::findAll(['status' => 'active']);

        assertType('array<yii2\extensions\phpstan\tests\stub\User>', $userRecords);
    }

    public function testReturnUserOrNullWhenFindOneByCondition(): void
    {
        $userRecord = User::findOne(['id' => 1]);

        assertType('yii2\extensions\phpstan\tests\stub\User|null', $userRecord);
    }

    public function testReturnUserQueryWhenFindBySql(): void
    {
        $userQuery = User::findBySql('SELECT * FROM users');

        assertType('yii\db\ActiveQuery<yii2\extensions\phpstan\tests\stub\User>', $userQuery);
    }

    public function testReturnUserQueryWhenFindBySqlWithChaining(): void
    {
        $query = User::findBySql('SELECT * FROM users')->andWhere(['active' => 1])->limit(10);

        assertType('yii\db\ActiveQuery<yii2\extensions\phpstan\tests\stub\User>', $query);
    }

    public function testReturnUserQueryWhenFindOnUser(): void
    {
        $userQuery = User::find();

        assertType('yii\db\ActiveQuery<yii2\extensions\phpstan\tests\stub\User>', $userQuery);
    }

    public function testReturnUserWhenInstantiating(): void
    {
        $userModel = new User();

        assertType('yii2\extensions\phpstan\tests\stub\User', $userModel);
    }
}
