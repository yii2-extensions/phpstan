<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\type;

use yii\db\ActiveRecord;
use yii2\extensions\phpstan\tests\support\stub\{
    Article,
    ArticleDraft,
    Book,
    Measurement,
    ModelWithConflictingProperty,
    ModelWithMultipleBehaviors,
    NestedSetsModel,
    Post,
    User,
};

use function PHPStan\Testing\assertType;

/**
 * Type assertion fixture for {@see ActiveRecord::getAttribute()} return types in PHPStan analysis.
 *
 * Verifies type inference from model PHPDoc and behavior property definitions, including tags inherited from parent
 * classes and traits, precedence of model properties over behavior properties on conflict, nullsafe receivers, and the
 * `mixed` fallback for unknown attributes and unpacked arguments.
 */
final class ActiveRecordGetAttributeDynamicMethodReturnType
{
    public function testReturnInheritedPropertyTypeWhenGetAttributeOnModelWithoutOwnTags(): void
    {
        $article = new Article();

        assertType('int', $article->getAttribute('id'));
        assertType('string', $article->getAttribute('title'));
        assertType('int|string', $article->getAttribute('revision'));
        assertType('mixed', $article->getAttribute('password'));
        assertType('mixed', $article->getAttribute('isNewRecord'));
    }

    public function testReturnIntAndStringWhenGetAttributeWithMultipleBehaviors(): void
    {
        $model = new ModelWithMultipleBehaviors();

        assertType('int', $model->getAttribute('lft'));
        assertType('string', $model->getAttribute('slug'));
    }

    public function testReturnIntWhenGetAttributeWithBehaviorPhpDoc(): void
    {
        $model = new NestedSetsModel();

        assertType('int', $model->getAttribute('lft'));
        assertType('int', $model->getAttribute('rgt'));
        assertType('int', $model->getAttribute('depth'));
    }

    public function testReturnMixedWhenGetAttributeArgumentIsUnpacked(): void
    {
        $post = new Post();

        assertType('mixed', $post->getAttribute(...['title']));
    }

    public function testReturnMixedWhenGetAttributeWithBehaviorPhpDoc(): void
    {
        $model = new NestedSetsModel();

        assertType('mixed', $model->getAttribute('unknown_attribute'));
    }

    public function testReturnMixedWhenGetAttributeWithModelPhpDoc(): void
    {
        $post = new Post();

        assertType('mixed', $post->getAttribute('unknown_attribute'));
    }

    public function testReturnNearestPropertyTypeWhenGetAttributeOnModelOverridingInheritedTags(): void
    {
        $draft = new ArticleDraft();

        assertType('string|null', $draft->getAttribute('title'));
        assertType('string', $draft->getAttribute('note'));
        assertType('int', $draft->getAttribute('revision'));
        assertType('int', $draft->getAttribute('id'));
        assertType('string', $draft->getAttribute('slug'));
    }

    public function testReturnNullablePropertyTypeWhenGetAttributeOnNullsafeReceiver(User|null $user): void
    {
        assertType('int|null', $user?->getAttribute('id'));
    }

    public function testReturnResolvedPropertyTypeWhenGetAttributeOnModelWithGenericParent(): void
    {
        $measurement = new Measurement();

        assertType('float', $measurement->getAttribute('value'));
    }

    public function testReturnStringWhenGetAttributeWithModelPhpDoc(): void
    {
        $post = new Post();

        assertType('string', $post->getAttribute('title'));
        assertType('string', $post->getAttribute('content'));
    }

    public function testReturnStringWhenGetAttributeWithModelPhpDocTakesPrecedenceOverBehavior(): void
    {
        $model = new ModelWithConflictingProperty();

        assertType('string', $model->getAttribute('lft'));
    }

    public function testReturnTagTypeWhenGetAttributeOnRelationAndReadOnlyTags(): void
    {
        $book = new Book();

        assertType('array<yii2\extensions\phpstan\tests\support\stub\Comment>', $book->getAttribute('comments'));
        assertType('yii2\extensions\phpstan\tests\support\stub\User', $book->getAttribute('author'));
        assertType('string', $book->getAttribute('label'));
        assertType('mixed', $book->getAttribute('secret'));
    }
}
