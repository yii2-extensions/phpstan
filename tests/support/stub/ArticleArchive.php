<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

/**
 * Stub concrete ActiveRecord model redeclaring an inherited readable `@property` tag as write-only for precedence tests.
 *
 * @phpstan-property-write string $title
 */
final class ArticleArchive extends ArticleRecord {}
