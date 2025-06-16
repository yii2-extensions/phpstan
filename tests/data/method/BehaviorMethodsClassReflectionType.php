<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\method;

use yii2\extensions\phpstan\tests\stub\MyComponent;

use function PHPStan\Testing\assertType;

/**
 * Data provider for method reflection of Yii Behaviors in PHPStan analysis.
 *
 * Validates type inference and return types for methods provided by attached behaviors on {@see MyComponent}, ensuring
 * that PHPStan correctly recognizes and infers types for available methods as if they were natively declared on the
 * component class.
 *
 * These tests cover scenarios including direct method calls, behavior-provided methods, parameterized methods, and
 * shared method resolution, verifying that type assertions match the expected return types for each case.
 *
 * Key features.
 * - Coverage for parameterized and shared methods.
 * - Ensures compatibility with PHPStan method reflection for Yii behaviors.
 * - Type assertion for native and behavior-provided methods.
 * - Validates correct type inference for all supported method signatures.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class BehaviorMethodsClassReflectionType
{
    public function testReturnIntegerFromMethodWithReturnType(): void
    {
        $component = new MyComponent();

        assertType('int', $component->methodWithReturnType());
    }

    public function testReturnStringFromBehaviorOneMethod(): void
    {
        $component = new MyComponent();

        assertType('string', $component->behaviorOneMethod());
    }

    public function testReturnStringFromBehaviorTwoMethod(): void
    {
        $component = new MyComponent();

        assertType('string', $component->behaviorTwoMethod());
    }

    public function testReturnStringFromMethodWithParameters(): void
    {
        $component = new MyComponent();

        assertType('string', $component->methodWithParameters('test', 123));
    }

    public function testReturnStringFromSharedMethod(): void
    {
        $component = new MyComponent();

        assertType('string', $component->sharedMethod());
    }

    public function testReturnTypeInferenceForMethodWithoutExplicitReturnType(): void
    {
        $component = new MyComponent();

        assertType('string', $component->methodWithoutReturnType());
    }
}
