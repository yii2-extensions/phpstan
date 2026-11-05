<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\action\hook;

use yii\base\Action;

/**
 * Action whose `id` hook comes from {@see IdHookTrait}.
 */
class TraitHookedIdAction extends Action
{
    use IdHookTrait;

    public function run(): string
    {
        return static::class;
    }
}
