<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\action;

use yii\base\{Action, Controller};

/**
 * Controller that overrides `createAction()` and forwards to the parent.
 */
class OverridingParentController extends Controller
{
    public function createAction($id): Action|null
    {
        return parent::createAction($id);
    }
}
