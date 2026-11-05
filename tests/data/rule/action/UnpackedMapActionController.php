<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\action;

use yii\base\Controller;
use yii2\extensions\phpstan\tests\support\action\TargetAction;

/**
 * Controller whose map unpacks the parent map.
 */
final class UnpackedMapActionController extends Controller
{
    public function actions(): array
    {
        return [...parent::actions(), 'unknown' => ['class' => TargetAction::class, 'colour' => 'red']];
    }
}
