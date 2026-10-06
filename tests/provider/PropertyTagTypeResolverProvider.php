<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use yii2\extensions\phpstan\tests\support\stub\{Article, ArticleArchive, ArticleDraft, ArticleRecord, Book, Measurement};

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\PropertyTagTypeResolverTest} test cases.
 *
 * Provides class and property name pairs with the readable type their nearest `@property` tag resolves to, or `null`.
 */
final class PropertyTagTypeResolverProvider
{
    /**
     * @return iterable<string, array{class-string, string, string|null}>
     */
    public static function readableTypeProvider(): iterable
    {
        yield 'class tag overriding parent tag' => [ArticleDraft::class, 'title', 'string|null'];
        yield 'framework tag' => [Article::class, 'isNewRecord', null];
        yield 'interface tag' => [ArticleDraft::class, 'slug', 'string'];
        yield 'parent tag' => [Article::class, 'title', 'string'];
        yield 'read-only tag' => [Book::class, 'label', 'string'];
        yield 'template tag bound by subclass' => [Measurement::class, 'value', 'float'];
        yield 'trait tag overriding parent tag' => [ArticleDraft::class, 'revision', 'int'];
        yield 'undeclared property' => [Article::class, 'missing', null];
        yield 'write-only tag hiding parent tag' => [ArticleArchive::class, 'title', null];
        yield 'write-only tag' => [ArticleRecord::class, 'password', null];
    }
}
