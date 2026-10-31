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
    /**
     * Shares its name with a {@see BehaviorOne} property of another type, so the type shows which behavior wins.
     */
    public int $sharedProperty = 2;

    public function behaviorTwoMethod(): string
    {
        return 'behavior two';
    }

    /**
     * Shares its name with a {@see BehaviorOne} method of another return type, so the type shows which behavior wins.
     */
    public function sharedMethod(): int
    {
        return 2;
    }
}
