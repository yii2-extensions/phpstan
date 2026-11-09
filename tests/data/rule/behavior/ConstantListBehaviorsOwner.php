<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\behavior;

use yii\base\Component;
use yii2\extensions\phpstan\tests\support\behavior\{AttachableBehavior, NotAttachableObject};

/**
 * Component whose `behaviors()` returns a list built away from the return, which native types prove constant.
 */
final class ConstantListBehaviorsOwner extends Component
{
    private const array DEFINITIONS = [
        'attachable' => AttachableBehavior::class,
        'notAttachable' => NotAttachableObject::class,
    ];

    public function behaviors(): array
    {
        $definitions = self::DEFINITIONS;

        return $definitions;
    }
}
