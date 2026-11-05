<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\action;

use stdClass;
use yii\base\Controller;
use yii2\extensions\phpstan\tests\support\action\{
    AbstractAction,
    BehaviorsAction,
    ConstructBoundAction,
    ConsumingConstructorAction,
    ExtraParamConstructorAction,
    FactoryAction,
    ForwardingConstructorAction,
    MappedAction,
    ParamsBoundAction,
    PlainObject,
    SetterBoundAction,
    SingletonAction,
    TargetAction,
};
use yii2\extensions\phpstan\tests\support\createobject\ComponentBehavior;

/**
 * Controller whose external action definitions have an assignment path that isn't proven.
 */
final class StandAsideActionController extends Controller
{
    public function actions(): array
    {
        return [
            'getterOnly' => [
                'class' => TargetAction::class,
                'size' => 1,
            ],
            'staticProperty' => [
                'class' => TargetAction::class,
                'shared' => 'value',
            ],
            'behaviorsMethod' => [
                'class' => BehaviorsAction::class,
                'colour' => 'red',
            ],
            'mappedBehavior' => [
                'class' => MappedAction::class,
                'colour' => 'red',
            ],
            'behaviorAfterOption' => [
                'class' => TargetAction::class,
                'colour' => 'red',
                'as paint' => ComponentBehavior::class,
            ],
            'forwardingConstructor' => [
                'class' => ForwardingConstructorAction::class,
                'colour' => 'red',
            ],
            'consumingConstructor' => [
                'class' => ConsumingConstructorAction::class,
                'colour' => 'red',
            ],
            'extraParamConstructor' => [
                'class' => ExtraParamConstructorAction::class,
                'colour' => 'red',
            ],
            'legacyClassNull' => [
                '__class' => null,
                'class' => TargetAction::class,
                'colour' => 'red',
            ],
            'constructKeyZero' => [
                'class' => TargetAction::class,
                '__construct()' => ['other'],
                'colour' => 'red',
            ],
            'constructKeyTwo' => [
                'class' => TargetAction::class,
                '__construct()' => [2 => ['colour' => 'red']],
            ],
            'constructNamed' => [
                'class' => TargetAction::class,
                '__construct()' => ['config' => []],
                'colour' => 'red',
            ],
            'paramsBound' => [
                'class' => ParamsBoundAction::class,
                'colour' => 'red',
            ],
            'constructBound' => [
                'class' => ConstructBoundAction::class,
                'colour' => 'red',
            ],
            'setterBound' => [
                'class' => SetterBoundAction::class,
                'colour' => 'red',
            ],
            'singleton' => [
                'class' => SingletonAction::class,
                'colour' => 'red',
            ],
            'factory' => [
                'class' => FactoryAction::class,
                'colour' => 'red',
            ],
            'abstractAction' => [
                'class' => AbstractAction::class,
                'colour' => 'red',
            ],
            'nonAction' => [
                'class' => PlainObject::class,
                'colour' => 'red',
            ],
            'internalClass' => [
                'class' => stdClass::class,
                'colour' => 'red',
            ],
            'missingClass' => [
                'class' => 'Missing\\Action',
                'colour' => 'red',
            ],
            'dynamicClass' => [
                'class' => $this->actionClass(),
                'colour' => 'red',
            ],
            'dynamicKey' => [
                'class' => TargetAction::class,
                $this->optionName() => 'red',
            ],
            'callable' => static fn(string $id, Controller $controller): TargetAction => new TargetAction(
                $id,
                $controller,
            ),
            'nullDefinition' => null,
            '' => [
                'class' => TargetAction::class,
                'colour' => 'red',
            ],
        ];
    }

    private function actionClass(): string
    {
        return TargetAction::class;
    }

    private function optionName(): string
    {
        return 'colour';
    }
}
