<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\widget;

/**
 * Subclass of {@see TargetWidget} whose `widget()` comes from {@see WidgetTrait}.
 */
class TraitOverrideWidget extends TargetWidget
{
    use WidgetTrait;
}
