<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\action;

use yii\base\Controller;
use yii2\extensions\phpstan\tests\support\action\TargetAction;

/**
 * Controller that returns a whole map held by a constant-known variable.
 */
final class ConstantMapActionController extends Controller
{
    public function actions(): array
    {
        $actions = [
            'unknown' => ['class' => TargetAction::class, 'colour' => 'red'],
            'type' => ['class' => TargetAction::class, 'count' => 'all'],
        ];

        return $actions;
    }
}
