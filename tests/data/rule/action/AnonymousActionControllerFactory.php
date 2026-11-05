<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\action;

use yii\base\{Controller, Module};
use yii2\extensions\phpstan\tests\support\action\TargetAction;

/**
 * Creates an anonymous controller whose `actions()` names an unknown option.
 */
final class AnonymousActionControllerFactory
{
    /**
     * @param array<string, mixed> $config
     */
    public function create(Module $module, array $config): Controller
    {
        return new class ('anonymous', $module, $config) extends Controller {
            public function actions(): array
            {
                return ['unknown' => ['class' => TargetAction::class, 'colour' => 'red']];
            }
        };
    }
}
