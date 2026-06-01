<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\type;

use yii\db\ActiveRecord;
use yii2\extensions\phpstan\tests\support\stub\{Category, MyActiveRecord, User};

use function PHPStan\Testing\assertType;

/**
 * Type assertion fixture for {@see ActiveRecord} static method return types in PHPStan analysis.
 *
 * Verifies type inference for {@see ActiveRecord::find()}, {@see ActiveRecord::findOne()},
 * {@see ActiveRecord::findAll()}, and {@see ActiveRecord::findBySql()} on custom {@see ActiveRecord} implementations,
 * covering chained calls and array versus object result scenarios.
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
        assertType(
            'yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Category>',
            Category::find(),
        );
    }

    public function testReturnMyActiveRecordArrayQueryWhenFindAsArray(): void
    {
        assertType(
            'yii\db\ActiveQuery<array{flag: bool}>',
            MyActiveRecord::find()->asArray(),
        );
    }

    public function testReturnMyActiveRecordArrayWhenFindAllWithIds(): void
    {
        assertType(
            'array<yii2\extensions\phpstan\tests\support\stub\MyActiveRecord>',
            MyActiveRecord::findAll([1, 2, 3]),
        );
    }

    public function testReturnMyActiveRecordOrNullWhenFindOneAfterChaining(): void
    {
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\MyActiveRecord|null',
            MyActiveRecord::find()->where(['status' => 'published'])->one(),
        );
    }

    public function testReturnMyActiveRecordOrNullWhenFindOneById(): void
    {
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\MyActiveRecord|null',
            MyActiveRecord::findOne(1),
        );
    }

    public function testReturnMyActiveRecordQueryWhenFind(): void
    {
        assertType(
            'yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\MyActiveRecord>',
            MyActiveRecord::find(),
        );
    }

    public function testReturnMyActiveRecordQueryWhenFindBySqlWithParameters(): void
    {
        assertType(
            'yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\MyActiveRecord>',
            MyActiveRecord::findBySql('SELECT * FROM my_table WHERE id = :id', [':id' => 1]),
        );
    }

    public function testReturnMyActiveRecordQueryWhenFindWithChaining(): void
    {
        assertType(
            'yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\MyActiveRecord>',
            MyActiveRecord::find()->where(['status' => 'active'])->orderBy('created_at DESC'),
        );
    }

    public function testReturnMyActiveRecordWhenInstantiating(): void
    {
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\MyActiveRecord',
            new MyActiveRecord(),
        );
    }

    public function testReturnUserArrayQueryWhenFindAsArray(): void
    {
        assertType(
            'yii\db\ActiveQuery<array{id: int, name: string, email: string}>',
            User::find()->asArray(),
        );
    }

    public function testReturnUserArrayWhenFindAllAfterChaining(): void
    {
        assertType(
            'array<int, yii2\extensions\phpstan\tests\support\stub\User>',
            User::find()->where(['active' => 1])->orderBy('name ASC')->all(),
        );
    }

    public function testReturnUserArrayWhenFindAllWithCondition(): void
    {
        assertType(
            'array<yii2\extensions\phpstan\tests\support\stub\User>',
            User::findAll(['status' => 'active']),
        );
    }

    public function testReturnUserOrNullWhenFindOneByCondition(): void
    {
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\User|null',
            User::findOne(['id' => 1]),
        );
    }

    public function testReturnUserQueryWhenFindBySql(): void
    {
        assertType(
            'yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\User>',
            User::findBySql('SELECT * FROM users'),
        );
    }

    public function testReturnUserQueryWhenFindBySqlWithChaining(): void
    {
        assertType(
            'yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\User>',
            User::findBySql('SELECT * FROM users')->andWhere(['active' => 1])->limit(10),
        );
    }

    public function testReturnUserQueryWhenFindOnUser(): void
    {
        assertType(
            'yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\User>',
            User::find(),
        );
    }

    public function testReturnUserWhenInstantiating(): void
    {
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\User',
            new User(),
        );
    }
}
