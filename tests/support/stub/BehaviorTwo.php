<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

use yii\base\Behavior;

/**
 * Stub behavior exposing array and scalar properties and methods for component extension tests.
 *
 * @template T of MyComponent
 * @extends Behavior<T>
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
