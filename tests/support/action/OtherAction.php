<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\action;

use yii\base\Action;

/**
 * Action that the container builds for {@see BoundAction}, with a `colour` property that `BoundAction` lacks.
 */
final class OtherAction extends Action
{
    public string $colour = '';

    public function run(): string
    {
        return self::class;
    }
}
