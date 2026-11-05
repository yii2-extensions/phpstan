<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\action;

use yii\base\{Action, Controller};
use yii2\extensions\phpstan\tests\support\action\{BoundAction, ChildAction, OptionsBoundAction, OtherAction, TargetAction};

/**
 * Final controller whose external action definitions name options that the created action can't assign.
 */
final class UnknownOptionActionController extends Controller
{
    private const array DEFINITION = ['class' => TargetAction::class, 'colour' => 'red'];

    private const string OPTION = 'colour';

    public function actions(): array
    {
        $definition = [
            'class' => TargetAction::class,
            'colour' => 'red',
        ];

        return [
            'unknown' => [
                'class' => TargetAction::class,
                'colour' => 'red',
            ],
            'multiLine' => [
                'class' => TargetAction::class,
                'name' => 'value',
                'colour' => 'red',
            ],
            'baseAction' => [
                'class' => Action::class,
                'colour' => 'red',
            ],
            'inherited' => [
                'class' => ChildAction::class,
                'shade' => 'dark',
                'colour' => 'red',
            ],
            'protectedProperty' => [
                'class' => TargetAction::class,
                'internal' => 'value',
            ],
            'caseMismatch' => [
                'class' => TargetAction::class,
                'Name' => 'value',
            ],
            'optionBeforeSetter' => [
                'class' => TargetAction::class,
                'colour' => 'red',
                'label' => 'text',
            ],
            'legacyClass' => [
                '__class' => TargetAction::class,
                'colour' => 'red',
            ],
            'legacyClassWins' => [
                'class' => TargetAction::class,
                '__class' => OtherAction::class,
                'name' => 'value',
            ],
            'boundClass' => [
                'class' => BoundAction::class,
                'name' => 'value',
            ],
            'aliasId' => [
                'class' => 'action.bound',
                'name' => 'value',
            ],
            'optionsBound' => [
                'class' => OptionsBoundAction::class,
                'colour' => 'red',
            ],
            'constantKey' => [
                'class' => TargetAction::class,
                self::OPTION => 'red',
            ],
            'constantDefinition' => self::DEFINITION,
            'variableDefinition' => $definition,
            'dynamicValue' => [
                'class' => TargetAction::class,
                'colour' => $this->id,
            ],
        ];
    }
}
