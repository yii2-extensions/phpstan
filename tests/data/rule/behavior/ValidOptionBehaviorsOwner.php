<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\behavior;

use yii\base\Component;
use yii2\extensions\phpstan\tests\support\behavior\{
    OptionBehavior,
    OptionDefaultsBehavior,
    OptionRedirectedBehavior,
    OptionTargetBehavior,
};

/**
 * Component whose `behaviors()` returns configurations whose options the created behavior assigns.
 */
final class ValidOptionBehaviorsOwner extends Component
{
    /**
     * @return array<array-key, mixed>
     */
    public function behaviors(): array
    {
        return [
            'property' => [
                'class' => OptionBehavior::class,
                'label' => 'value',
            ],
            'setter' => [
                'class' => OptionBehavior::class,
                'writable' => 'value',
            ],
            'protectedSetter' => [
                'class' => OptionBehavior::class,
                'protectedSetter' => 'value',
            ],
            'inheritedProperty' => [
                'class' => OptionBehavior::class,
                'inherited' => 'value',
            ],
            'inheritedSetter' => [
                'class' => OptionBehavior::class,
                'parentSetter' => 'value',
            ],
            // `attach()` overwrites it
            'owner' => [
                'class' => OptionBehavior::class,
                'owner' => null,
            ],
            'dunderWins' => [
                '__class' => OptionTargetBehavior::class,
                'class' => OptionBehavior::class,
                'onlyOnTarget' => 1,
            ],
            'containerId' => [
                'class' => 'optionBehaviorId',
                'label' => 'value',
            ],
            // the option belongs to the class that the container creates
            'redirected' => [
                'class' => OptionRedirectedBehavior::class,
                'onlyOnTarget' => 1,
            ],
            'definitionOptions' => [
                'class' => OptionDefaultsBehavior::class,
                'preset' => 'call',
            ],
        ];
    }
}
