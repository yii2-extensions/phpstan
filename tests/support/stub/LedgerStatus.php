<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

/**
 * Stub backed enum used as the type of a cast column `@property` tag on {@see Ledger}.
 */
enum LedgerStatus: string
{
    case Closed = 'closed';
    case Open = 'open';
}
