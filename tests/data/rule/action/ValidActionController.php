<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\action;

use yii\base\{Action, Controller};
use yii2\extensions\phpstan\tests\support\action\{BoundAction, OptionsBoundAction, OtherAction, TargetAction};
use yii2\extensions\phpstan\tests\support\createobject\ComponentBehavior;

/**
 * Controller whose external action definitions Yii assigns completely.
 */
final class ValidActionController extends Controller
{
    public function actions(): array
    {
        return [
            'properties' => ['class' => TargetAction::class, 'name' => 'value', 'count' => 1],
            'setter' => ['class' => TargetAction::class, 'label' => 'text'],
            'coercedValue' => ['class' => TargetAction::class, 'count' => '5'],
            'classOnly' => ['class' => TargetAction::class],
            'legacyClass' => ['__class' => TargetAction::class, 'name' => 'value'],
            'legacyClassWins' => ['class' => OtherAction::class, '__class' => TargetAction::class, 'name' => 'value'],
            'event' => ['class' => TargetAction::class, 'on beforeRun' => static fn(): bool => true],
            'behavior' => ['class' => TargetAction::class, 'as paint' => ComponentBehavior::class, 'colour' => 'red'],
            'idOption' => ['class' => TargetAction::class, 'id' => 'renamed'],
            'controllerOption' => ['class' => TargetAction::class, 'controller' => null],
            'boundClass' => ['class' => BoundAction::class, 'colour' => 'red'],
            'aliasId' => ['class' => 'action.bound', 'colour' => 'red'],
            'optionsBound' => ['class' => OptionsBoundAction::class, 'shade' => 'dark'],
            'baseAction' => ['class' => Action::class, 'id' => 'base'],
            'classString' => TargetAction::class,
        ];
    }
}
