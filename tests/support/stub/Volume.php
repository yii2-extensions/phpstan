<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

/**
 * Stub concrete ActiveRecord model inheriting relation and read-only `@property` tags from a parent class, a trait, and
 * an interface for `asArray()` row shape tests.
 *
 * @property string $isbn
 */
final class Volume extends LibraryRecord implements Labelled
{
    use Reviewed;
}
