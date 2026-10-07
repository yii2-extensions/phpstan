<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

/**
 * Stub trait declaring a nullable to-one relation `@property` tag that {@see Volume} inherits.
 *
 * @phpstan-property-read User|null $reviewer
 */
trait Reviewed {}
