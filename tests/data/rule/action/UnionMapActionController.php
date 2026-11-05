<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\action;

use yii\base\Controller;
use yii2\extensions\phpstan\tests\support\action\TargetAction;

/**
 * Controller whose map is one of two constant maps.
 */
final class UnionMapActionController extends Controller
{
    public function actions(): array
    {
        $actions = $this->id === 'probe'
            ? ['unknown' => ['class' => TargetAction::class, 'colour' => 'red']]
            : ['unknown' => ['class' => TargetAction::class, 'shade' => 'dark']];

        return $actions;
    }
}
