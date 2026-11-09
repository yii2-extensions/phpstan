<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\behavior;

use yii\base\Component;
use yii2\extensions\phpstan\tests\support\behavior\NotAttachableObject;

/**
 * Component whose `behaviors()` returns lists that aren't one constant array.
 */
class DynamicBehaviorsOwner extends Component
{
    /**
     * @var array<string, string>
     */
    public array $extra = [];

    public function behaviors(): array
    {
        if ($this->extra !== []) {
            return [
                ...$this->extra,
                'notAttachable' => NotAttachableObject::class,
            ];
        }

        return [
            ...parent::behaviors(),
            'notAttachable' => NotAttachableObject::class,
        ];
    }
}
