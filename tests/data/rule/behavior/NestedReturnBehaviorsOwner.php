<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\behavior;

use yii\base\Component;
use yii2\extensions\phpstan\tests\support\behavior\{AttachableBehavior, NotAttachableObject};

/**
 * Component whose `behaviors()` contains a closure returning a list that isn't the method's return.
 */
final class NestedReturnBehaviorsOwner extends Component
{
    public function behaviors(): array
    {
        $build = static function (): array {
            return ['notAttachable' => NotAttachableObject::class];
        };

        return [
            'attachable' => AttachableBehavior::class,
            'built' => $build,
        ];
    }
}
