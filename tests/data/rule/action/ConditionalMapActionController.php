<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\action;

use yii\base\Controller;
use yii2\extensions\phpstan\tests\support\action\TargetAction;

/**
 * Controller whose map has an optional action ID.
 */
final class ConditionalMapActionController extends Controller
{
    public function actions(): array
    {
        $actions = ['valid' => ['class' => TargetAction::class]];

        if ($this->id === 'probe') {
            $actions['unknown'] = ['class' => TargetAction::class, 'colour' => 'red'];
        }

        return $actions;
    }
}
