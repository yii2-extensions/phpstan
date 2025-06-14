<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\fixture\data\type;

use yii2\extensions\phpstan\tests\stub\MyComponent;

use function PHPStan\Testing\assertType;

/**
 * Data provider for dynamic method return types from Yii behaviors in PHPStan analysis.
 *
 * Validates type inference and return types for dynamic methods provided by attached behaviors on {@see MyComponent},
 * ensuring that PHPStan correctly recognizes and infers types for behavior-injected dynamic methods, including those
 * with parameters and native return types.
 *
 * These tests ensure that static analysis and IDE autocompletion provide accurate type information for all dynamic
 * methods contributed by behaviors, covering scenarios with unique, shared, and parameterized methods, as well as
 * native component methods.
 *
 * Key features.
 * - Coverage for dynamic methods with parameters and explicit return types.
 * - Ensures correct type inference for native component methods alongside behavior methods.
 * - Type assertions for dynamic methods provided by multiple behaviors.
 * - Validation of dynamic shared method resolution across behaviors.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class BehaviorDynamicMethodReturnType
{
    public function testReturnStringFromBehaviorOneMethod(): void
    {
        $component = new MyComponent();

        $result = $component->behaviorOneMethod();

        assertType('string', $result);
    }

    public function testReturnStringFromBehaviorTwoMethod(): void
    {
        $component = new MyComponent();

        $result = $component->behaviorTwoMethod();

        assertType('string', $result);
    }

    public function testReturnStringFromSharedMethod(): void
    {
        $component = new MyComponent();

        $result = $component->sharedMethod();

        assertType('string', $result);
    }

    public function testReturnStringFromMethodWithParameters(): void
    {
        $component = new MyComponent();

        $result = $component->methodWithParameters('test', 123);

        assertType('string', $result);
    }

    public function testReturnIntegerFromMethodWithReturnType(): void
    {
        $component = new MyComponent();

        $result = $component->methodWithReturnType();

        assertType('int', $result);
    }

    public function testReturnStringFromNativeMethod(): void
    {
        $component = new MyComponent();

        $result = $component->nativeMethod();

        assertType('string', $result);
    }
}
