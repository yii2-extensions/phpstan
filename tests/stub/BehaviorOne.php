<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\stub;

use yii\base\Behavior;

/**
 * Provides additional properties and methods for components via a Yii behavior mechanism.
 *
 * This class defines several properties and methods to be attached to a component, enabling dynamic extension of
 * component functionality at runtime. It demonstrates the use of typed and mixed properties, as well as method
 * definitions with and without parameters, for testing and extension scenarios in Yii Applications.
 *
 * @template T of MyComponent
 * @extends Behavior<T>
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
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

    public function sharedMethod(): string
    {
        return 'from behavior one';
    }

    public function methodWithParameters(string $param1, int $param2): string
    {
        return 'method with parameters';
    }

    public function methodWithReturnType(): int
    {
        return 42;
    }

    /**
     * @phpstan-return string
     */
    public function methodWithoutReturnType()
    {
        return "test string";
    }
}
