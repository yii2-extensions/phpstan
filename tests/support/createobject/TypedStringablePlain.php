<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\createobject;

use Stringable;

/**
 * `Stringable` subclass of {@see TypedPlain}.
 */
final class TypedStringablePlain extends TypedPlain implements Stringable
{
    public function __toString(): string
    {
        return 'plain';
    }
}
