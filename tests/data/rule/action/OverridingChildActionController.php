<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\action;

use yii2\extensions\phpstan\tests\support\action\TargetAction;

/**
 * Child of {@see OverridingParentController} whose `actions()` names an unknown option.
 */
final class OverridingChildActionController extends OverridingParentController
{
    public function actions(): array
    {
        return ['unknown' => ['class' => TargetAction::class, 'colour' => 'red']];
    }
}
