<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\action;

/**
 * Class without a constructor that isn't an action.
 */
final class PlainObject
{
    public string $colour = '';
}
