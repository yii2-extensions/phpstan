<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\behavior;

use yii2\extensions\phpstan\tests\support\behavior\NotAttachableObject;

/**
 * Trait that declares `behaviors()`, whose returns the rule leaves to the using classes' own declarations.
 */
trait BehaviorsListTrait
{
    public function behaviors(): array
    {
        return ['notAttachable' => NotAttachableObject::class];
    }
}
