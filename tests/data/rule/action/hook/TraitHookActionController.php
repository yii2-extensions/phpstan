<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\action\hook;

use yii\base\Controller;

/**
 * Controller whose external actions hook `id` through a trait, which PHP supports from 8.5.
 */
final class TraitHookActionController extends Controller
{
    public function actions(): array
    {
        return [
            'traitHookedId' => ['class' => TraitHookedIdAction::class, 'colour' => 'blue'],
            'traitHookedChild' => ['class' => TraitHookedChildAction::class, 'colour' => 'blue'],
        ];
    }
}
