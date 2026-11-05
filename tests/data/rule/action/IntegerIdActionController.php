<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\action;

use yii\base\Controller;
use yii2\extensions\phpstan\tests\support\action\TargetAction;

/**
 * Controller whose map has an integer action ID.
 */
final class IntegerIdActionController extends Controller
{
    public function actions(): array
    {
        return [0 => ['class' => TargetAction::class, 'colour' => 'red']];
    }
}
