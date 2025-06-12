<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\fixture\data\types;

use yii\db\{ActiveQuery, ActiveRecord};
use yii2\extensions\phpstan\tests\stub\{Category, MyActiveRecord, User};

use function PHPStan\Testing\assertType;

/**
 * Test suite for dynamic method return types of {@see ActiveRecord} relations in Yii Active Record scenarios.
 *
 * Validates type inference and return types for instance relation methods such as {@see MyActiveRecord::hasMany()} and
 * {@see MyActiveRecord::hasOne()} in custom {@see ActiveRecord} implementations, including chained query calls and
 * array/object result scenarios.
 *
 * These tests ensure that PHPStan correctly infers the result types for relation methods returning query objects,
 * arrays, and related models, and that type safety is preserved across chained query calls and various result
 * scenarios.
 *
 * Key features.
 * - Chained query method return types ({@see ActiveQuery::asArray()}, {@see ActiveQuery::orderBy()},
 *   {@see ActiveQuery::limit()}, {@see ActiveQuery::where()}, {@see ActiveQuery::andWhere()}).
 * - Result types for {@see ActiveQuery::all()}, {@see ActiveQuery::one()} on relation queries.
 * - Type assertions for array and object results from relation queries.
 * - Type assertions for relation methods: {@see hasMany()}, {@see hasOne()}.
 * - Type inference for relations with both class-string and string class names.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class ActiveRecordDynamicMethodReturnType
{
    public function testReturnCategoryArrayQueryWhenHasManyAsArray(): void
    {
        $model = new MyActiveRecord();

        $relation = $model->hasMany(Category::class, ['parent_id' => 'id'])->asArray();

        assertType('yii\db\ActiveQuery<array{id: int, name: string, parent_id: int|null}>', $relation);
    }

    public function testReturnCategoryArrayWhenHasManyAsArrayWithAll(): void
    {
        $model = new MyActiveRecord();

        $relationResults = $model->hasMany(Category::class, ['parent_id' => 'id'])->asArray()->all();

        assertType('array<int, array{id: int, name: string, parent_id: int|null}>', $relationResults);
    }

    public function testReturnCategoryArrayWhenHasManyWithAll(): void
    {
        $model = new MyActiveRecord();

        $relationResults = $model->hasMany(Category::class, ['parent_id' => 'id'])->all();

        assertType('array<int, yii2\extensions\phpstan\tests\stub\Category>', $relationResults);
    }

    public function testReturnCategoryQueryWhenHasManyChainedWithOrderAndLimit(): void
    {
        $model = new MyActiveRecord();

        $relation = $model->hasMany(Category::class, ['parent_id' => 'id'])->orderBy('name ASC')->limit(10);

        assertType('yii\db\ActiveQuery<yii2\extensions\phpstan\tests\stub\Category>', $relation);
    }

    public function testReturnCategoryQueryWhenHasManyWithCategoryClass(): void
    {
        $model = new MyActiveRecord();

        $relation = $model->hasMany(Category::class, ['parent_id' => 'id']);

        assertType('yii\db\ActiveQuery<yii2\extensions\phpstan\tests\stub\Category>', $relation);
    }

    public function testReturnCategoryQueryWhenHasManyWithStringClass(): void
    {
        $model = new User();

        $relation = $model->hasMany('yii2\extensions\phpstan\tests\stub\Category', ['user_id' => 'id']);

        assertType('yii\db\ActiveQuery<yii2\extensions\phpstan\tests\stub\Category>', $relation);
    }

    public function testReturnUserArrayQueryWhenHasOneAsArray(): void
    {
        $model = new MyActiveRecord();

        $relation = $model->hasOne(User::class, ['id' => 'user_id'])->asArray();

        assertType('yii\db\ActiveQuery<array{id: int, name: string, email: string}>', $relation);
    }

    public function testReturnUserArrayWhenHasOneAsArrayWithOne(): void
    {
        $model = new MyActiveRecord();

        $relationResult = $model->hasOne(User::class, ['id' => 'user_id'])->asArray()->one();

        assertType('array{id: int, name: string, email: string}|null', $relationResult);
    }

    public function testReturnUserOrNullWhenHasOneWithOne(): void
    {
        $model = new MyActiveRecord();

        $relationResult = $model->hasOne(User::class, ['id' => 'user_id'])->one();

        assertType('yii2\extensions\phpstan\tests\stub\User|null', $relationResult);
    }

    public function testReturnUserQueryWhenHasOneChainedWithWhereConditions(): void
    {
        $model = new MyActiveRecord();

        $relation = $model
            ->hasOne(User::class, ['id' => 'user_id'])
            ->where(['active' => 1])
            ->andWhere(['status' => 'published']);

        assertType('yii\db\ActiveQuery<yii2\extensions\phpstan\tests\stub\User>', $relation);
    }

    public function testReturnUserQueryWhenHasOneWithStringClass(): void
    {
        $model = new Category();

        $relation = $model->hasOne('yii2\extensions\phpstan\tests\stub\User', ['id' => 'user_id']);

        assertType('yii\db\ActiveQuery<yii2\extensions\phpstan\tests\stub\User>', $relation);
    }

    public function testReturnUserQueryWhenHasOneWithUserClass(): void
    {
        $model = new MyActiveRecord();

        $relation = $model->hasOne(User::class, ['id' => 'user_id']);

        assertType('yii\db\ActiveQuery<yii2\extensions\phpstan\tests\stub\User>', $relation);
    }
}
