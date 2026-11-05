<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\action;

use yii\base\Controller;
use yii2\extensions\phpstan\tests\support\action\TargetAction;

/**
 * Controller whose map has a dynamic action ID.
 */
final class DynamicIdActionController extends Controller
{
    public function actions(): array
    {
        return [$this->id => ['class' => TargetAction::class, 'colour' => 'red']];
    }
}
