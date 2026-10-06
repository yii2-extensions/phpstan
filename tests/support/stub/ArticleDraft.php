<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

/**
 * Stub concrete ActiveRecord model overriding inherited `@property` tags and inheriting tags from a trait and an
 * interface for precedence tests.
 *
 * @property string|null $title
 * @property string $note
 */
final class ArticleDraft extends ArticleRecord implements Publishable
{
    use ArticleRevision;
}
