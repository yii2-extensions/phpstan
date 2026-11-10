<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\behavior;

use yii\base\Behavior;

/**
 * Behavior that overrides the constructor of `BaseObject`.
 */
class ConstructorBehavior extends Behavior
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(public mixed $required = null, array $config = [])
    {
        parent::__construct($config);
    }
}
