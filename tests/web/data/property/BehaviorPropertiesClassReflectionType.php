<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\web\fixture\data\property;

use yii2\extensions\phpstan\tests\stub\MyComponent;

use function PHPStan\Testing\assertType;

/**
 * Data provider for property reflection of Yii Behaviors in PHPStan analysis.
 *
 * Validates type inference and return types for properties provided by attached behaviors on {@see MyComponent},
 * ensuring that PHPStan correctly recognizes and infers types for available properties as if they were natively
 * declared on the component class.
 *
 * These tests cover scenarios including direct property access, behavior-provided properties, parameterized properties,
 * and shared property resolution, verifying that type assertions match the expected return types for each case.
 *
 * Key features.
 * - Coverage for parameterized and shared properties.
 * - Ensures compatibility with PHPStan property reflection for Yii behaviors.
 * - Type assertion for native and behavior-provided properties.
 * - Validates correct type inference for all supported property types.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class BehaviorPropertiesClassReflectionType
{
    public function testReturnArrayFromArrayProperty(): void
    {
        $component = new MyComponent();

        $result = $component->arrayProperty;

        assertType('array<mixed>', $result);
    }

    public function testReturnBooleanFromBooleanProperty(): void
    {
        $component = new MyComponent();

        $result = $component->booleanProperty;

        assertType('bool', $result);
    }

    public function testReturnIntegerFromBehaviorTwoProperty(): void
    {
        $component = new MyComponent();

        $result = $component->behaviorTwoProperty;

        assertType('int', $result);
    }

    public function testReturnMixedFromMixedProperty(): void
    {
        $component = new MyComponent();

        $result = $component->mixedProperty;

        assertType('mixed', $result);
    }

    public function testReturnStringFromBehaviorOneProperty(): void
    {
        $component = new MyComponent();

        $result = $component->behaviorOneProperty;

        assertType('string', $result);
    }

    public function testReturnStringFromSharedProperty(): void
    {
        $component = new MyComponent();

        $result = $component->sharedProperty;

        assertType('string', $result);
    }

    public function testReturnStringFromVirtualProperty(): void
    {
        $component = new MyComponent();

        $result = $component->virtualProperty;

        assertType('string', $result);
    }
}
