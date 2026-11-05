<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\action;

use yii\base\Controller;
use yii2\extensions\phpstan\tests\support\action\TargetAction;

/**
 * Controller whose map extends a map of unknown keys.
 */
final class OpenMapActionController extends Controller
{
    public function actions(): array
    {
        $actions = $this->baseActions();

        $actions['unknown'] = ['class' => TargetAction::class, 'colour' => 'red'];

        return $actions;
    }

    /**
     * @return array<string, mixed>
     */
    private function baseActions(): array
    {
        return ['unknown' => ['class' => TargetAction::class, 'colour' => 'red']];
    }
}
