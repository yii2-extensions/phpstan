<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\action;

use yii\base\Action;

/**
 * Action bound to {@see OtherAction} in the container, with a `name` property that `OtherAction` lacks.
 */
final class BoundAction extends Action
{
    public string $name = '';

    public function run(): string
    {
        return self::class;
    }
}
