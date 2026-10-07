<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

/**
 * Stub interface declaring a read-only scalar `@property` tag that implementing models such as {@see Volume} inherit.
 *
 * @phpstan-property-read string $label
 */
interface Labelled {}
