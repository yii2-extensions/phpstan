<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

/**
 * Stub trait declaring a `@property` tag that takes precedence over the tag inherited from {@see ArticleRecord}.
 *
 * @property int $revision
 */
trait ArticleRevision {}
