<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\widget;

use yii\base\Widget;

/**
 * Widget with only the public property `colour`, which the container builds for {@see BoundWidget}.
 */
class OtherWidget extends Widget
{
    public string $colour = '';

    public function run(): string
    {
        return static::class;
    }
}
