<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\createobject;

/**
 * Backed enum used as a native property type of {@see TypedTarget} and as an option value.
 */
enum TypedSuit: string
{
    case Hearts = 'H';
}
