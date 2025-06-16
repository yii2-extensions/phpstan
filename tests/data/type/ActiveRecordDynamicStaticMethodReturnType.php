<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\type;

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
 *   {@see ActiveRecord::findBySql()} with and without chaining.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class ActiveRecordDynamicStaticMethodReturnType
{
    public function testReturnCategoryArrayQueryWhenFindBySqlWithAsArray(): void
    {
        assertType(
            'yii\db\ActiveQuery<array{id: int, name: string, parent_id: int|null}>',
            Category::findBySql('SELECT * FROM categories')->asArray(),
        );
    }

    public function testReturnCategoryQueryWhenFindOnCategory(): void
    {
        assertType('yii\db\ActiveQuery<yii2\extensions\phpstan\tests\stub\Category>', Category::find());
    }

    public function testReturnMyActiveRecordArrayQueryWhenFindAsArray(): void
    {
        assertType('yii\db\ActiveQuery<array{flag: bool}>', MyActiveRecord::find()->asArray());
    }

    public function testReturnMyActiveRecordArrayWhenFindAllWithIds(): void
    {
        assertType('array<yii2\extensions\phpstan\tests\stub\MyActiveRecord>', MyActiveRecord::findAll([1, 2, 3]));
    }

    public function testReturnMyActiveRecordOrNullWhenFindOneAfterChaining(): void
    {
        assertType(
            'yii2\extensions\phpstan\tests\stub\MyActiveRecord|null',
            MyActiveRecord::find()->where(['status' => 'published'])->one(),
        );
    }

    public function testReturnMyActiveRecordOrNullWhenFindOneById(): void
    {
        assertType('yii2\extensions\phpstan\tests\stub\MyActiveRecord|null', MyActiveRecord::findOne(1));
    }

    public function testReturnMyActiveRecordQueryWhenFind(): void
    {
        assertType('yii\db\ActiveQuery<yii2\extensions\phpstan\tests\stub\MyActiveRecord>', MyActiveRecord::find());
    }

    public function testReturnMyActiveRecordQueryWhenFindBySqlWithParameters(): void
    {
        assertType(
            'yii\db\ActiveQuery<yii2\extensions\phpstan\tests\stub\MyActiveRecord>',
            MyActiveRecord::findBySql('SELECT * FROM my_table WHERE id = :id', [':id' => 1]),
        );
    }

    public function testReturnMyActiveRecordQueryWhenFindWithChaining(): void
    {
        assertType(
            'yii\db\ActiveQuery<yii2\extensions\phpstan\tests\stub\MyActiveRecord>',
            MyActiveRecord::find()->where(['status' => 'active'])->orderBy('created_at DESC'),
        );
    }

    public function testReturnMyActiveRecordWhenInstantiating(): void
    {
        assertType('yii2\extensions\phpstan\tests\stub\MyActiveRecord', new MyActiveRecord());
    }

    public function testReturnUserArrayQueryWhenFindAsArray(): void
    {
        assertType('yii\db\ActiveQuery<array{id: int, name: string, email: string}>', User::find()->asArray());
    }

    public function testReturnUserArrayWhenFindAllAfterChaining(): void
    {
        assertType(
            'array<int, yii2\extensions\phpstan\tests\stub\User>',
            User::find()->where(['active' => 1])->orderBy('name ASC')->all(),
        );
    }

    public function testReturnUserArrayWhenFindAllWithCondition(): void
    {
        assertType('array<yii2\extensions\phpstan\tests\stub\User>', User::findAll(['status' => 'active']));
    }

    public function testReturnUserOrNullWhenFindOneByCondition(): void
    {
        assertType('yii2\extensions\phpstan\tests\stub\User|null', User::findOne(['id' => 1]));
    }

    public function testReturnUserQueryWhenFindBySql(): void
    {
        assertType(
            'yii\db\ActiveQuery<yii2\extensions\phpstan\tests\stub\User>',
            User::findBySql('SELECT * FROM users'),
        );
    }

    public function testReturnUserQueryWhenFindBySqlWithChaining(): void
    {
        assertType(
            'yii\db\ActiveQuery<yii2\extensions\phpstan\tests\stub\User>',
            User::findBySql('SELECT * FROM users')->andWhere(['active' => 1])->limit(10),
        );
    }

    public function testReturnUserQueryWhenFindOnUser(): void
    {
        assertType('yii\db\ActiveQuery<yii2\extensions\phpstan\tests\stub\User>', User::find());
    }

    public function testReturnUserWhenInstantiating(): void
    {
        assertType('yii2\extensions\phpstan\tests\stub\User', new User());
    }
}
