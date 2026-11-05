<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\action;

use stdClass;
use yii\base\Controller;
use yii2\extensions\phpstan\tests\support\action\{BoundAction, TargetAction};

/**
 * Non-final concrete controller whose external action definitions assign values that the created action never accepts.
 */
class InvalidOptionTypeActionController extends Controller
{
    private const array DEFINITION = ['class' => TargetAction::class, 'count' => 'all'];

    public function actions(): array
    {
        return [
            'arrayForString' => ['class' => TargetAction::class, 'name' => []],
            'arrayForSetter' => ['class' => TargetAction::class, 'label' => []],
            'nonNumericString' => ['class' => TargetAction::class, 'count' => 'all'],
            'objectForClass' => ['class' => TargetAction::class, 'value' => new stdClass()],
            'boundClass' => ['class' => BoundAction::class, 'colour' => []],
            'legacyClass' => ['__class' => TargetAction::class, 'count' => []],
            'constantDefinition' => self::DEFINITION,
        ];
    }
}
