<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\widget;

use RuntimeException;

/**
 * Subclass of {@see TargetWidget} whose `run()` fails, so that only `widget()` runs into it.
 */
class RunFailingWidget extends TargetWidget
{
    public function run(): string
    {
        throw new RuntimeException('Run reached.');
    }
}
