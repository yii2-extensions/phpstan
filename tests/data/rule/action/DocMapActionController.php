<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\action;

use yii\base\Controller;
use yii2\extensions\phpstan\tests\support\action\TargetAction;

/**
 * Controller whose map is typed only by PHPDoc.
 */
final class DocMapActionController extends Controller
{
    public function actions(): array
    {
        /** @var array{unknown: array{class: class-string, colour: string}} $actions */
        $actions = $this->baseActions();

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
