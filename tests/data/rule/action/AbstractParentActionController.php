<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\action;

use yii\base\Controller;
use yii2\extensions\phpstan\tests\support\action\TargetAction;

/**
 * Abstract controller whose `actions()` names an unknown option.
 */
abstract class AbstractParentActionController extends Controller
{
    public function actions(): array
    {
        return ['unknown' => ['class' => TargetAction::class, 'colour' => 'red']];
    }
}
