<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\action;

use yii\base\{Action, Controller};
use yii2\extensions\phpstan\tests\support\action\TargetAction;

/**
 * Controller that overrides `createAction()` and ignores its map.
 */
final class CreateActionOverrideController extends Controller
{
    public function actions(): array
    {
        return ['unknown' => ['class' => TargetAction::class, 'colour' => 'red']];
    }

    public function createAction($id): Action|null
    {
        return new TargetAction($id, $this);
    }
}
