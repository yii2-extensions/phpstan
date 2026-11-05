<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\action;

use yii\base\Controller;
use yii2\extensions\phpstan\tests\support\action\TargetAction;

/**
 * Controller whose `actions()` returns one of two maps.
 */
final class MultipleReturnsActionController extends Controller
{
    public function actions(): array
    {
        if ($this->id === 'other') {
            return ['unknown' => ['class' => TargetAction::class, 'colour' => 'red']];
        }

        return ['unknown' => ['class' => TargetAction::class, 'name' => 'value']];
    }
}
