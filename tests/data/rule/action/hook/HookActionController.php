<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\action\hook;

use yii\base\Controller;
use yii2\extensions\phpstan\tests\support\action\hook\{
    HookedControllerAction,
    HookedIdAction,
    PassThroughHookAction,
    TraitHookedChildAction,
    TraitHookedIdAction,
};

/**
 * Controller whose external actions hook `id` or `controller`, which `Action::__construct()` writes before the configuration.
 */
final class HookActionController extends Controller
{
    public function actions(): array
    {
        return [
            'hookedId' => ['class' => HookedIdAction::class, 'colour' => 'blue'],
            'hookedIdType' => ['class' => HookedIdAction::class, 'count' => 'all'],
            'hookedController' => ['class' => HookedControllerAction::class, 'colour' => 'blue'],
            'passThroughHook' => ['class' => PassThroughHookAction::class, 'colour' => 'blue'],
            'traitHookedId' => ['class' => TraitHookedIdAction::class, 'colour' => 'blue'],
            'traitHookedChild' => ['class' => TraitHookedChildAction::class, 'colour' => 'blue'],
        ];
    }
}
