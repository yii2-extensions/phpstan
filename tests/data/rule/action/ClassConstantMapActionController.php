<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\action;

use yii\base\Controller;
use yii2\extensions\phpstan\tests\support\action\TargetAction;

/**
 * Controller that returns a whole map held by a class constant.
 */
final class ClassConstantMapActionController extends Controller
{
    private const array ACTIONS = [
        'unknown' => ['class' => TargetAction::class, 'colour' => 'red'],
        'type' => ['class' => TargetAction::class, 'count' => 'all'],
    ];

    public function actions(): array
    {
        return self::ACTIONS;
    }
}
