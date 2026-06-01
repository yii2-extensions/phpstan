<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\method;

use yii2\extensions\phpstan\tests\support\stub\MyComponent;

use function PHPStan\Testing\assertType;

/**
 * Type assertion fixture for behavior method reflection on {@see MyComponent} in PHPStan analysis.
 *
 * Verifies type inference for methods provided by attached behaviors, including parameterized, shared, and native
 * methods, as if they were declared on the component class.
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
