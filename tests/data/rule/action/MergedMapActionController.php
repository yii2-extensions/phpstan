<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\action;

use yii\base\Controller;
use yii2\extensions\phpstan\tests\support\action\TargetAction;

use function array_merge;

/**
 * Controller whose map merges the parent map.
 */
final class MergedMapActionController extends Controller
{
    public function actions(): array
    {
        return array_merge(parent::actions(), ['unknown' => ['class' => TargetAction::class, 'colour' => 'red']]);
    }
}
