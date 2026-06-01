<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\property;

use yii2\extensions\phpstan\tests\support\stub\MyComponent;

use function PHPStan\Testing\assertType;

/**
 * Type assertion fixture for behavior property reflection on {@see MyComponent} in PHPStan analysis.
 *
 * Verifies type inference for properties provided by attached behaviors, including parameterized, shared, virtual, and
 * native properties, as if they were declared on the component class.
 */
final class BehaviorPropertiesClassReflectionType
{
    public function testReturnArrayFromArrayProperty(): void
    {
        $component = new MyComponent();

        assertType('array<mixed>', $component->arrayProperty);
    }

    public function testReturnBooleanFromBooleanProperty(): void
    {
        $component = new MyComponent();

        assertType('bool', $component->booleanProperty);
    }

    public function testReturnIntegerFromBehaviorTwoProperty(): void
    {
        $component = new MyComponent();

        assertType('int', $component->behaviorTwoProperty);
    }

    public function testReturnMixedFromMixedProperty(): void
    {
        $component = new MyComponent();

        assertType('mixed', $component->mixedProperty);
    }

    public function testReturnStringFromBehaviorOneProperty(): void
    {
        $component = new MyComponent();

        assertType('string', $component->behaviorOneProperty);
    }

    public function testReturnStringFromSharedProperty(): void
    {
        $component = new MyComponent();

        assertType('string', $component->sharedProperty);
    }

    public function testReturnStringFromVirtualProperty(): void
    {
        $component = new MyComponent();

        assertType('string', $component->virtualProperty);
    }
}
