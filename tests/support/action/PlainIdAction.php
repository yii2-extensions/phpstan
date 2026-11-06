<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\action;

use yii\base\Action;

/**
 * Action whose `id` comes from {@see PlainIdTrait}, an identical redeclaration.
 */
final class PlainIdAction extends Action
{
    use PlainIdTrait;

    public function run(): string
    {
        return static::class;
    }
}
