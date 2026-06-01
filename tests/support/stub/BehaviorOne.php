<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\stub;

use yii\base\Behavior;

/**
 * Stub behavior exposing typed and `mixed` properties and methods for component extension tests.
 *
 * @template T of MyComponent
 * @extends Behavior<T>
 */
final class BehaviorOne extends Behavior
{
    public string $behaviorOneProperty = 'behavior one property';
    public bool $booleanProperty = true;
    public mixed $mixedProperty = null;
    public string $sharedProperty = 'from behavior one';

    public function behaviorOneMethod(): string
    {
        return 'behavior one';
    }

    /**
     * @phpstan-return string
     */
    public function methodWithoutReturnType(): string
    {
        return 'test string';
    }

    public function methodWithParameters(string $param1, int $param2): string
    {
        return 'method with parameters';
    }

    public function methodWithReturnType(): int
    {
        return 42;
    }

    public function sharedMethod(): string
    {
        return 'from behavior one';
    }
}
