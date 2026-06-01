<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\type;

use yii\db\ActiveRecord;
use yii2\extensions\phpstan\tests\support\stub\{Category, MyActiveRecord, User};

use function PHPStan\Testing\assertType;

/**
 * Type assertion fixture for {@see ActiveRecord} relation method return types in PHPStan analysis.
 *
 * Verifies type inference for {@see MyActiveRecord::hasMany()} and {@see MyActiveRecord::hasOne()} on custom
 * {@see ActiveRecord} implementations, covering chained calls, array versus object results, and both class-string and
 * string class names.
 */
final class ActiveRecordDynamicMethodReturnType
{
    public function testReturnCategoryArrayQueryWhenHasManyAsArray(): void
    {
        $model = new MyActiveRecord();

        assertType(
            'yii\db\ActiveQuery<array{id: int, name: string, parent_id: int|null}>',
            $model->hasMany(Category::class, ['parent_id' => 'id'])->asArray(),
        );
    }

    public function testReturnCategoryArrayWhenHasManyAsArrayWithAll(): void
    {
        $model = new MyActiveRecord();

        assertType(
            'array<int, array{id: int, name: string, parent_id: int|null}>',
            $model->hasMany(Category::class, ['parent_id' => 'id'])->asArray()->all(),
        );
    }

    public function testReturnCategoryArrayWhenHasManyWithAll(): void
    {
        $model = new MyActiveRecord();

        assertType(
            'array<int, yii2\extensions\phpstan\tests\support\stub\Category>',
            $model->hasMany(Category::class, ['parent_id' => 'id'])->all(),
        );
    }

    public function testReturnCategoryQueryWhenHasManyChainedWithOrderAndLimit(): void
    {
        $model = new MyActiveRecord();

        assertType(
            'yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Category>',
            $model->hasMany(Category::class, ['parent_id' => 'id'])->orderBy('name ASC')->limit(10),
        );
    }

    public function testReturnCategoryQueryWhenHasManyWithCategoryClass(): void
    {
        $model = new MyActiveRecord();

        assertType(
            'yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Category>',
            $model->hasMany(Category::class, ['parent_id' => 'id']),
        );
    }

    public function testReturnCategoryQueryWhenHasManyWithStringClass(): void
    {
        $model = new User();

        assertType(
            'yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Category>',
            $model->hasMany('yii2\extensions\phpstan\tests\support\stub\Category', ['user_id' => 'id']),
        );
    }

    public function testReturnUserArrayQueryWhenHasOneAsArray(): void
    {
        $model = new MyActiveRecord();

        assertType(
            'yii\db\ActiveQuery<array{id: int, name: string, email: string}>',
            $model->hasOne(User::class, ['id' => 'user_id'])->asArray(),
        );
    }

    public function testReturnUserArrayWhenHasOneAsArrayWithOne(): void
    {
        $model = new MyActiveRecord();

        assertType(
            'array{id: int, name: string, email: string}|null',
            $model->hasOne(User::class, ['id' => 'user_id'])->asArray()->one(),
        );
    }

    public function testReturnUserOrNullWhenHasOneWithOne(): void
    {
        $model = new MyActiveRecord();

        assertType(
            'yii2\extensions\phpstan\tests\support\stub\User|null',
            $model->hasOne(User::class, ['id' => 'user_id'])->one(),
        );
    }

    public function testReturnUserQueryWhenHasOneChainedWithWhereConditions(): void
    {
        $model = new MyActiveRecord();

        assertType(
            'yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\User>',
            $model
                ->hasOne(User::class, ['id' => 'user_id'])
                ->where(['active' => 1])
                ->andWhere(['status' => 'published']),
        );
    }

    public function testReturnUserQueryWhenHasOneWithStringClass(): void
    {
        $model = new Category();

        assertType(
            'yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\User>',
            $model->hasOne('yii2\extensions\phpstan\tests\support\stub\User', ['id' => 'user_id']),
        );
    }

    public function testReturnUserQueryWhenHasOneWithUserClass(): void
    {
        $model = new MyActiveRecord();

        assertType(
            'yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\User>',
            $model->hasOne(User::class, ['id' => 'user_id']),
        );
    }
}
