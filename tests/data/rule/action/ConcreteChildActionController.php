<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\action;

use yii2\extensions\phpstan\tests\support\action\TargetAction;

/**
 * Child of {@see ConcreteParentActionController} that replaces the definition.
 */
final class ConcreteChildActionController extends ConcreteParentActionController
{
    public function actions(): array
    {
        return ['unknown' => ['class' => TargetAction::class, 'name' => 'value']];
    }
}
