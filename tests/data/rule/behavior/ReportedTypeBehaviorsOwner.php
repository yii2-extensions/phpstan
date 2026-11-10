<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\behavior;

use yii\base\Component;
use yii2\extensions\phpstan\tests\support\behavior\{
    OptionRedirectedBehavior,
    OptionTargetBehavior,
    TypedOptionBehavior,
};

/**
 * Component whose `behaviors()` returns configurations that set values the created behavior never accepts.
 */
final class ReportedTypeBehaviorsOwner extends Component
{
    /**
     * @return array<array-key, mixed>
     */
    public function behaviors(): array
    {
        return [
            // anonymous
            [
                'class' => TypedOptionBehavior::class,
                'count' => 'all',
            ],
            'afterValid' => [
                'class' => TypedOptionBehavior::class,
                'title' => 'x',
                'count' => 'all',
            ],
            'constructorArgument' => [
                'class' => TypedOptionBehavior::class,
                '__construct()' => [['count' => 'all']],
            ],
            'dunderWins' => [
                '__class' => TypedOptionBehavior::class,
                'class' => OptionTargetBehavior::class,
                'count' => 'all',
            ],
            'nullForInt' => [
                'class' => TypedOptionBehavior::class,
                'count' => null,
            ],
            'property' => [
                'class' => TypedOptionBehavior::class,
                'count' => 'all',
            ],
            // Yii assigns a protected property through its setter
            'protectedProperty' => [
                'class' => TypedOptionBehavior::class,
                'guarded' => [],
            ],
            // the option belongs to the class that the container creates
            'redirected' => [
                'class' => OptionRedirectedBehavior::class,
                'targetCount' => 'all',
            ],
            'setter' => [
                'class' => TypedOptionBehavior::class,
                'tags' => 'news',
            ],
        ];
    }
}
