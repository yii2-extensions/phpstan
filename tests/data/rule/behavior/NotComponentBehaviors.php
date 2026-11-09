<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\behavior;

use yii2\extensions\phpstan\tests\support\behavior\NotAttachableObject;

/**
 * Class that isn't a component, whose `behaviors()` Yii never reads.
 */
final class NotComponentBehaviors
{
    /**
     * @return array<string, string>
     */
    public function behaviors(): array
    {
        return ['notAttachable' => NotAttachableObject::class];
    }
}
