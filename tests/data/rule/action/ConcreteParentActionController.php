<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\action;

use yii\base\Controller;
use yii2\extensions\phpstan\tests\support\action\TargetAction;

/**
 * Non-final concrete controller whose `actions()` names an unknown option.
 */
class ConcreteParentActionController extends Controller
{
    public function actions(): array
    {
        return ['unknown' => ['class' => TargetAction::class, 'colour' => 'red']];
    }
}
