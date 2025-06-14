<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\stub;

use yii\base\Behavior;

/**
 * Provides additional array and scalar properties and methods for components via a Yii behavior mechanism.
 *
 * This class defines array and scalar properties, as well as methods to be attached to a component, enabling dynamic
 * extension of component functionality at runtime. It demonstrates the use of typed properties and method definitions
 * for testing and extension scenarios in Yii Applications.
 *
 * @template T of MyComponent
 * @extends Behavior<T>
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class BehaviorTwo extends Behavior
{
    /**
     * @phpstan-var array<array-key, mixed>
     */
    public array $arrayProperty = [];
    public int $behaviorTwoProperty = 42;
    public string $sharedProperty = 'from behavior two';

    public function behaviorTwoMethod(): string
    {
        return 'behavior two';
    }

    public function sharedMethod(): string
    {
        return 'from behavior two';
    }
}
