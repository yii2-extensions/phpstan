<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\type;

use yii\db\{ActiveQuery, ActiveRecord, Exception};
use yii2\extensions\phpstan\tests\support\stub\{
    Article,
    ArticleDraft,
    Book,
    Category,
    Comment,
    Ledger,
    Measurement,
    MyActiveRecord,
    Post,
    User,
    Volume,
};

use function array_key_exists;
use function PHPStan\Testing\assertType;

/**
 * Type assertion fixture for {@see ActiveQuery} dynamic method return types in PHPStan analysis.
 *
 * Verifies type inference for query and result methods on custom {@see ActiveRecord} implementations, covering chained
 * calls, generic and non-generic custom query classes, array versus object result scenarios, row shapes built from
 * inherited `@property` tags, optional keys for relation and read-only tags, nullsafe receivers, and unpacked
 * arguments.
 */
final class ActiveQueryDynamicMethodReturnType
{
    public function testReturnActiveQueryWhenAsArrayOnNonGenericCustomQuery(): void
    {
        $arrayQuery = Comment::find()->asArray();

        assertType(
            'yii\db\ActiveQuery<array{id: int, body: string}>',
            $arrayQuery,
        );
        assertType(
            'array{id: int, body: string}|null',
            $arrayQuery->one(),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\CommentQuery',
            Comment::find()->asArray(false),
        );
    }

    public function testReturnActiveQueryWhenAsArrayWithVariableArgument(): void
    {
        $userPreference = $_POST['format'] ?? 'default';
        $useArrayFormat = ($userPreference === 'json');

        assertType(
            'yii\db\ActiveQuery<array{flag: bool}|yii2\extensions\phpstan\tests\support\stub\MyActiveRecord>',
            MyActiveRecord::find()->asArray($useArrayFormat),
        );
    }

    public function testReturnActiveQueryWhenCustomQuerySubclass(): void
    {
        $customQuery = Post::find();

        assertType(
            'yii2\extensions\phpstan\tests\support\stub\PostQuery<yii2\extensions\phpstan\tests\support\stub\Post>',
            $customQuery,
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\PostQuery<array{title: string, content: string}>',
            $customQuery->asArray(),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\Post|null',
            $customQuery->one(),
        );
        assertType(
            'array<yii2\extensions\phpstan\tests\support\stub\Post>',
            $customQuery->all(),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\PostQuery<yii2\extensions\phpstan\tests\support\stub\Post>',
            $customQuery->published(),
        );
    }

    public function testReturnArrayShapeQueryWhenAsArrayOnArrayRows(): void
    {
        $asArray = getenv('RESPONSE_FORMAT') === 'array';

        $arrayQuery = MyActiveRecord::find()
            ->asArray()
            ->limit(10);

        assertType(
            'yii\db\ActiveQuery<array{flag: bool}>',
            $arrayQuery->asArray(),
        );
        assertType(
            'yii\db\ActiveQuery<array{flag: bool}>',
            $arrayQuery->asArray($asArray),
        );
    }

    public function testReturnArrayShapeWithInheritedRelationAndReadOnlyKeysWhenAsArray(): void
    {
        $arrayQuery = Volume::find()->asArray();

        assertType(
            'yii\db\ActiveQuery<array{isbn: string, reviewer?: array<string, mixed>|null, id: int, comments?: array<array<string, mixed>>, label?: string}>',
            $arrayQuery,
        );
        assertType(
            'array{isbn: string, reviewer?: array<string, mixed>|null, id: int, comments?: array<array<string, mixed>>, label?: string}|null',
            $arrayQuery->one(),
        );
        assertType(
            'array<array{isbn: string, reviewer?: array<string, mixed>|null, id: int, comments?: array<array<string, mixed>>, label?: string}>',
            $arrayQuery->all(),
        );

        $row = $arrayQuery->one();

        if ($row !== null) {
            assertType(
                'int',
                $row['id'],
            );
            assertType(
                'array<array<string, mixed>>',
                $row['comments'] ?? [],
            );
            assertType(
                'array<string, mixed>|null',
                $row['reviewer'] ?? null,
            );
            assertType(
                'string|null',
                $row['label'] ?? null,
            );
        }
    }

    public function testReturnClassifiedKeysWhenAsArrayOnModelWithBorderlineTagTypes(): void
    {
        $arrayQuery = Ledger::find()->asArray();

        assertType(
            'yii\db\ActiveQuery<array{id: int, tags: array<string>, attachment: array<mixed>|yii2\extensions\phpstan\tests\support\stub\Comment, payload: mixed, options: array<mixed>, meta: object, entries: array<string|yii2\extensions\phpstan\tests\support\stub\Comment>, drafts?: array<array<string, mixed>>, thread?: array<array<string, mixed>>, created_at: DateTimeImmutable, status: yii2\extensions\phpstan\tests\support\stub\LedgerStatus, owner: yii2\extensions\phpstan\tests\support\stub\MyComponent, history: array<DateTimeImmutable>}>',
            $arrayQuery,
        );

        $row = $arrayQuery->one();

        if ($row !== null) {
            assertType(
                'array<string>',
                $row['tags'],
            );
            assertType(
                'array<mixed>|yii2\extensions\phpstan\tests\support\stub\Comment',
                $row['attachment'],
            );
            assertType(
                'mixed',
                $row['payload'],
            );
            assertType(
                'array<mixed>',
                $row['options'],
            );
            assertType(
                'yii2\extensions\phpstan\tests\support\stub\LedgerStatus',
                $row['status'],
            );
            assertType(
                'yii2\extensions\phpstan\tests\support\stub\MyComponent',
                $row['owner'],
            );
            assertType(
                'array<array<string, mixed>>',
                $row['drafts'] ?? [],
            );
            assertType(
                'array<array<string, mixed>>',
                $row['thread'] ?? [],
            );
        }
    }

    public function testReturnCommentWhenNonGenericCustomQueryChained(): void
    {
        $customQuery = Comment::find()->approved();

        assertType(
            'yii2\extensions\phpstan\tests\support\stub\CommentQuery',
            $customQuery,
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\Comment|null',
            $customQuery->one(),
        );
        assertType(
            'array<yii2\extensions\phpstan\tests\support\stub\Comment>',
            $customQuery->all(),
        );
    }

    public function testReturnDeclaredQueryWhenAsArrayArgumentIsUnpacked(): void
    {
        assertType(
            'yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\MyActiveRecord>',
            MyActiveRecord::find()->asArray(...[false]),
        );
        assertType(
            'yii\db\ActiveQuery<array<string, mixed>>',
            MyActiveRecord::find()->asArray(...[true]),
        );
    }

    public function testReturnInheritedArrayShapeWhenAsArrayOnModelWithoutOwnTags(): void
    {
        $arrayQuery = Article::find()->asArray();

        assertType(
            'yii\db\ActiveQuery<array{id: int, title: string, revision: int|string}>',
            $arrayQuery,
        );

        $row = $arrayQuery->one();

        assertType(
            'array{id: int, title: string, revision: int|string}|null',
            $row,
        );

        if ($row !== null) {
            assertType(
                'int',
                $row['id'],
            );
        }
    }

    public function testReturnMergedArrayShapeWhenAsArrayOnModelOverridingInheritedTags(): void
    {
        assertType(
            'yii\db\ActiveQuery<array{title: string|null, note: string, revision: int, id: int, slug: string}>',
            ArticleDraft::find()->asArray(),
        );
    }

    public function testReturnModelOrArrayShapeQueryWhenAsArrayOnModelWithRelationTagsAndNonConstantArgument(): void
    {
        $asArray = getenv('RESPONSE_FORMAT') === 'array';

        assertType(
            'yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Book>',
            Book::find()->asArray(false),
        );
        assertType(
            'yii\db\ActiveQuery<array{id: int, title: string|null, category?: array<string, mixed>|null, comments?: array<array<string, mixed>>, author?: array<string, mixed>|null, label?: string}|yii2\extensions\phpstan\tests\support\stub\Book>',
            Book::find()->asArray($asArray),
        );
    }

    public function testReturnMyActiveRecordArrayQueryWhenAsArrayExplicitTrue(): void
    {
        assertType(
            'yii\db\ActiveQuery<array{flag: bool}>',
            MyActiveRecord::find()->asArray(true),
        );
    }

    public function testReturnMyActiveRecordArrayQueryWhenChainedWithAsArray(): void
    {
        $complexQuery = MyActiveRecord::find()
            ->where(['status' => 'active'])
            ->asArray()
            ->orderBy('created_at DESC')
            ->limit(10);

        assertType(
            'yii\db\ActiveQuery<array{flag: bool}>',
            $complexQuery,
        );
        assertType(
            'array<array{flag: bool}>',
            $complexQuery->all(),
        );
    }

    public function testReturnMyActiveRecordArrayWhenArraysWithCondition(): void
    {
        $arrayRecords = MyActiveRecord::find()->asArray()->where(['flag' => true])->all();

        assertType(
            'array<array{flag: bool}>',
            $arrayRecords,
        );

        foreach ($arrayRecords as $record) {
            assertType(
                'array{flag: bool}',
                $record,
            );
            assertType(
                'bool',
                $record['flag'],
            );
        }
    }

    public function testReturnMyActiveRecordArrayWhenAsArrayWithAll(): void
    {
        $arrayQuery = MyActiveRecord::find()->asArray();

        assertType(
            'yii\db\ActiveQuery<array{flag: bool}>',
            $arrayQuery,
        );
        assertType(
            'array<array{flag: bool}>',
            $arrayQuery->all(),
        );
    }

    public function testReturnMyActiveRecordArrayWhenFindAllWithCondition(): void
    {
        $modelRecords = MyActiveRecord::findAll('condition');

        assertType(
            'array<yii2\extensions\phpstan\tests\support\stub\MyActiveRecord>',
            $modelRecords,
        );

        foreach ($modelRecords as $record) {
            assertType('yii2\extensions\phpstan\tests\support\stub\MyActiveRecord', $record);
            assertType('bool', $record->flag);
        }
    }

    public function testReturnMyActiveRecordArrayWhenObjectsWithCondition(): void
    {
        $objectRecords = MyActiveRecord::find()
            ->asArray(false)
            ->where(['condition'])->all();

        assertType(
            'array<yii2\extensions\phpstan\tests\support\stub\MyActiveRecord>',
            $objectRecords,
        );

        foreach ($objectRecords as $record) {
            assertType(
                'yii2\extensions\phpstan\tests\support\stub\MyActiveRecord',
                $record,
            );
            assertType(
                'bool',
                $record->flag,
            );
            assertType(
                'mixed',
                $record['flag'],
            );
        }
    }

    /**
     * @throws Exception if an unexpected error occurs during execution.
     */
    public function testReturnMyActiveRecordOrNullWhenChainedWithOne(): void
    {
        $offsetProp = 'flag';
        $flag = false;

        assertType(
            '\'flag\'',
            $offsetProp,
        );
        assertType(
            'false',
            $flag,
        );

        $records = MyActiveRecord::find()
            ->where(['flag' => true])
            ->one();

        assertType(
            'yii2\extensions\phpstan\tests\support\stub\MyActiveRecord|null',
            $records,
        );

        if ($records !== null) {
            assertType(
                'yii2\extensions\phpstan\tests\support\stub\MyActiveRecord',
                $records,
            );
            assertType(
                'mixed',
                $records[$offsetProp],
            );
            assertType(
                'bool',
                $records->flag,
            );
            assertType(
                'bool',
                $records->save(),
            );
        }
    }

    public function testReturnMyActiveRecordOrNullWhenFindBySqlWithOne(): void
    {
        $queryFromSql = MyActiveRecord::findBySql('SELECT * FROM table');

        assertType(
            'yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\MyActiveRecord>',
            $queryFromSql,
        );

        $recordOne = $queryFromSql->one();

        assertType(
            'yii2\extensions\phpstan\tests\support\stub\MyActiveRecord|null',
            $recordOne,
        );

        if ($recordOne !== null) {
            assertType(
                'bool',
                $recordOne->flag,
            );
            assertType(
                'mixed',
                $recordOne['flag'],
            );
        }
    }

    public function testReturnMyActiveRecordOrNullWhenFindOneWithCondition(): void
    {
        $records = MyActiveRecord::findOne(['condition']);

        assertType(
            'yii2\extensions\phpstan\tests\support\stub\MyActiveRecord|null',
            $records,
        );

        if ($records !== null) {
            assertType(
                'bool',
                $records->flag,
            );
            assertType(
                'mixed',
                $records['flag'],
            );
        }
    }

    public function testReturnMyActiveRecordQueryWhenAsArrayExplicitFalse(): void
    {
        assertType(
            'yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\MyActiveRecord>',
            MyActiveRecord::find()->asArray(false),
        );
    }

    public function testReturnMyActiveRecordQueryWhenAsArrayWithNamedArgument(): void
    {
        assertType(
            'yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\MyActiveRecord>',
            MyActiveRecord::find()->asArray(value: false),
        );
        assertType(
            'yii\db\ActiveQuery<array{flag: bool}>',
            MyActiveRecord::find()->asArray(value: true),
        );
    }

    public function testReturnMyActiveRecordQueryWhenChainedWithConditions(): void
    {
        $query = MyActiveRecord::find();

        assertType(
            'yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\MyActiveRecord>',
            $query,
        );
        assertType(
            'yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\MyActiveRecord>',
            $query->where(['active' => 1]) -> andWhere(['status' => 'published']),
        );
    }

    public function testReturnNullableArrayShapeQueryWhenAsArrayOnNullsafeReceiver(User|null $user): void
    {
        assertType(
            'yii\db\ActiveQuery<array{id: int, name: string, parent_id: int|null}>|null',
            $user?->hasOne(Category::class, ['id' => 'category_id'])->asArray(),
        );
        assertType(
            'yii\db\ActiveQuery<yii2\extensions\phpstan\tests\support\stub\Category>|null',
            $user?->hasOne(Category::class, ['id' => 'category_id'])->asArray(false),
        );
    }

    public function testReturnOptionalRelationAndReadOnlyKeysWhenAsArrayOnModelWithRelationTags(): void
    {
        $arrayQuery = Book::find()->asArray();

        assertType(
            'yii\db\ActiveQuery<array{id: int, title: string|null, category?: array<string, mixed>|null, comments?: array<array<string, mixed>>, author?: array<string, mixed>|null, label?: string}>',
            $arrayQuery,
        );
        assertType(
            'array{id: int, title: string|null, category?: array<string, mixed>|null, comments?: array<array<string, mixed>>, author?: array<string, mixed>|null, label?: string}|null',
            $arrayQuery->one(),
        );
        assertType(
            'array<array{id: int, title: string|null, category?: array<string, mixed>|null, comments?: array<array<string, mixed>>, author?: array<string, mixed>|null, label?: string}>',
            $arrayQuery->all(),
        );
    }

    public function testReturnPostArrayWhenCustomQuerySubclassAsArray(): void
    {
        $arrayQuery = Post::find()->asArray();

        assertType(
            'array{title: string, content: string}|null',
            $arrayQuery->one(),
        );
        assertType(
            'yii2\extensions\phpstan\tests\support\stub\PostQuery<array{title: string, content: string}>',
            $arrayQuery->published(),
        );
    }

    public function testReturnRelationRowsWhenReadingKeysOfAsArrayRow(): void
    {
        $row = Book::find()->asArray()->one();

        if ($row !== null) {
            assertType(
                'int',
                $row['id'],
            );
            assertType(
                'string|null',
                $row['title'],
            );
            assertType(
                'array<array<string, mixed>>',
                $row['comments'] ?? [],
            );
            assertType(
                'array<string, mixed>|null',
                $row['author'] ?? null,
            );
            assertType(
                'string|null',
                $row['label'] ?? null,
            );

            if (isset($row['comments'])) {
                assertType(
                    'array<array<string, mixed>>',
                    $row['comments'],
                );
            }

            if (array_key_exists('author', $row)) {
                assertType(
                    'array<string, mixed>|null',
                    $row['author'],
                );
            }

            if (isset($row['label'])) {
                assertType(
                    'string',
                    $row['label'],
                );
            }
        }

        foreach (Book::find()->asArray()->all() as $record) {
            foreach ($record['comments'] ?? [] as $comment) {
                assertType(
                    'array<string, mixed>',
                    $comment,
                );
            }
        }
    }

    public function testReturnResolvedArrayShapeWhenAsArrayOnModelWithGenericParent(): void
    {
        assertType(
            'yii\db\ActiveQuery<array{id: int, value: float}>',
            Measurement::find()->asArray(),
        );
    }

    public function testReturnUnionResultsWhenAsArrayWithVariableArgument(): void
    {
        $configValue = getenv('RESPONSE_FORMAT');

        $asArray = $configValue === 'array';

        $results = MyActiveRecord::find()->asArray($asArray)->all();

        assertType(
            'array<array{flag: bool}|yii2\extensions\phpstan\tests\support\stub\MyActiveRecord>',
            $results,
        );

        foreach ($results as $result) {
            assertType(
                'array{flag: bool}|yii2\extensions\phpstan\tests\support\stub\MyActiveRecord',
                $result,
            );
        }
    }
}
