<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\behavior;

use yii\base\{Behavior, Component};
use yii2\extensions\phpstan\tests\support\behavior\{
    AttachableBehavior,
    OptionBehavior,
    OptionDefaultsBehavior,
    OptionRedirectedBehavior,
    OptionTargetBehavior,
};

/**
 * Component whose `behaviors()` returns configurations that set options the created behavior can't assign.
 */
final class ReportedOptionBehaviorsOwner extends Component
{
    /**
     * @return array<array-key, mixed>
     */
    public function behaviors(): array
    {
        return [
            'unknown' => [
                'class' => OptionBehavior::class,
                'missing' => 1,
            ],
            'protected' => [
                'class' => OptionBehavior::class,
                'hidden' => 1,
            ],
            // Yii compares option names case-sensitively
            'caseMismatch' => [
                'class' => OptionBehavior::class,
                'Label' => 1,
            ],
            // a behavior is no component: `as` and `on` keys are plain property names
            'asKey' => [
                'class' => OptionBehavior::class,
                'as nested' => AttachableBehavior::class,
            ],
            'onKey' => [
                'class' => OptionBehavior::class,
                'on event' => 'trim',
            ],
            'base' => [
                'class' => Behavior::class,
                'missing' => 1,
            ],
            'afterValid' => [
                'class' => OptionBehavior::class,
                'label' => 'value',
                'missing' => 1,
            ],
            'dunderWins' => [
                '__class' => OptionBehavior::class,
                'class' => OptionTargetBehavior::class,
                'onlyOnTarget' => 1,
            ],
            'containerId' => [
                'class' => 'optionBehaviorId',
                'missing' => 1,
            ],
            'aliasChain' => [
                'class' => 'aliasA',
                'missing' => 1,
            ],
            'redirected' => [
                'class' => OptionRedirectedBehavior::class,
                'missing' => 1,
            ],
            'definitionOptions' => [
                'class' => OptionDefaultsBehavior::class,
                'missing' => 1,
            ],
            'leadingSlash' => [
                'class' => '\yii2\extensions\phpstan\tests\support\behavior\OptionBehavior',
                'missing' => 1,
            ],
            'constructorArgument' => [
                'class' => OptionBehavior::class,
                '__construct()' => [
                    [
                        'missing' => 1,
                    ],
                ],
            ],
            // anonymous
            [
                'class' => OptionBehavior::class,
                'missing' => 1,
            ],
        ];
    }
}
