<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\action;

use yii\base\Controller;
use yii2\extensions\phpstan\tests\support\action\TargetAction;

/**
 * Controller whose external action assigns `module`, which only Yii 22 declares a setter for.
 */
final class ModuleOptionActionController extends Controller
{
    public function actions(): array
    {
        return [
            'module' => ['class' => TargetAction::class, 'module' => null],
        ];
    }
}
