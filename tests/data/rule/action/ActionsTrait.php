<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\action;

use yii2\extensions\phpstan\tests\support\action\TargetAction;

/**
 * Provides an `actions()` method that names an unknown option.
 */
trait ActionsTrait
{
    public function actions(): array
    {
        return ['unknown' => ['class' => TargetAction::class, 'colour' => 'red']];
    }
}
