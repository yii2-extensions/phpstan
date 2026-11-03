<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\createobject;

use Stringable;

/**
 * Final `Stringable` value that doesn't implement {@see TypedContract}.
 */
final class TypedValue implements Stringable
{
    public function __toString(): string
    {
        return 'value';
    }
}
