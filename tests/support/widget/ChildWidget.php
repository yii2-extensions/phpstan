<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\widget;

/**
 * Subclass of {@see TargetWidget} with the additional public property `shade`.
 */
class ChildWidget extends TargetWidget
{
    public string $shade = '';
}
