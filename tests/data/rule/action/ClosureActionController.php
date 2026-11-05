<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\action;

use yii\base\Controller;
use yii2\extensions\phpstan\tests\support\action\TargetAction;

/**
 * Controller whose `actions()` builds unrelated maps in a closure and an arrow function.
 */
final class ClosureActionController extends Controller
{
    public function actions(): array
    {
        $closure = static function (): array {
            return ['unknown' => ['class' => TargetAction::class, 'colour' => 'red']];
        };
        $arrow = static fn(): array => ['unknown' => ['class' => TargetAction::class, 'colour' => 'red']];

        return ['valid' => ['class' => TargetAction::class, 'name' => (string) count($closure() + $arrow())]];
    }
}
