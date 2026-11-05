<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\action;

/**
 * Subclass of {@see TargetAction} with one more public property.
 */
final class ChildAction extends TargetAction
{
    public string $shade = '';
}
