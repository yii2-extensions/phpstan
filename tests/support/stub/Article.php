<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

/**
 * Stub concrete ActiveRecord model without own `@property` tags for inherited attribute inference tests.
 */
final class Article extends ArticleRecord {}
