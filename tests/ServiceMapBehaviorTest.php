<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPUnit\Framework\TestCase;
use ReflectionException;
use RuntimeException;
use yii2\extensions\phpstan\ServiceMap;
use yii2\extensions\phpstan\tests\stub\{BehaviorOne, BehaviorTwo, MyComponent};

/**
 * Test suite for {@see ServiceMap} behavior resolution and validation logic.
 *
 * Verifies correct mapping and retrieval of behaviors for component classes from configuration files, ensuring robust
 * error handling for invalid or unsupported behavior structures.
 *
 * The tests cover scenarios including valid and invalid class names, behavior extraction, and exception handling for
 * misconfigured or malformed behavior arrays.
 *
 * Key features.
 * - Ensures compatibility with the provided configuration files.
 * - Resolves behaviors by class name (as string or class-string).
 * - Returns an empty array for classes with no behaviors or when not configured.
 * - Throws exceptions for invalid behavior definitions, non-array structures, and non-string IDs.
 * - Validates error handling for unsupported or malformed configuration files.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class ServiceMapBehaviorTest extends TestCase
{
    /**
     * Base path for configuration files used in tests.
     */
    private const BASE_PATH = __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;

    /**
     * @throws ReflectionException if the component definition is invalid or can't be resolved.
     */
    public function testReturnBehaviorsWhenValidClassIsClassString(): void
    {
        $serviceMap = new ServiceMap(self::BASE_PATH . 'phpstan-config.php');

        $behaviors = $serviceMap->getBehaviorsByClassName(MyComponent::class);

        self::assertSame(
            [
                BehaviorOne::class,
                BehaviorTwo::class,
            ],
            $behaviors,
            'ServiceMap should return behaviors for MyComponent class.',
        );
    }

    /**
     * @throws ReflectionException if the component definition is invalid or can't be resolved.
     */
    public function testReturnBehaviorsWhenValidClassIsString(): void
    {
        $serviceMap = new ServiceMap(self::BASE_PATH . 'phpstan-config.php');

        $behaviors = $serviceMap->getBehaviorsByClassName('yii2\extensions\phpstan\tests\stub\MyComponent');

        self::assertSame(
            [
                'yii2\extensions\phpstan\tests\stub\BehaviorOne',
                'yii2\extensions\phpstan\tests\stub\BehaviorTwo',
            ],
            $behaviors,
            'ServiceMap should return behaviors for MyComponent class.',
        );
    }

    /**
     * @throws ReflectionException if the component definition is invalid or can't be resolved.
     */
    public function testReturnEmptyArrayWhenClassHasNotBehaviors(): void
    {
        $serviceMap = new ServiceMap(self::BASE_PATH . 'phpstan-config.php');

        $behaviors = $serviceMap->getBehaviorsByClassName('NonExistentClass');

        self::assertSame(
            [],
            $behaviors,
            'ServiceMap should return empty array for class with no behaviors.',
        );
    }

    /**
     * @throws ReflectionException if the component definition is invalid or can't be resolved.
     */
    public function testReturnEmptyArrayWhenNotBehaviorsConfigured(): void
    {
        $serviceMap = new ServiceMap(self::BASE_PATH . 'phpstan-config.php');

        $behaviors = $serviceMap->getBehaviorsByClassName('AnyClass');

        self::assertSame(
            [],
            $behaviors,
            'ServiceMap should return empty array when no behaviors are configured.',
        );
    }

    /**
     * @throws ReflectionException if the component definition is invalid or can't be resolved.
     */
    public function testThrowExceptionWhenBehaviorDefinitionNotArray(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Behavior definition for \'MyComponent\' must be an array.');

        new ServiceMap(self::BASE_PATH . 'behaviors-unsupported-definition-not-array.php');
    }

    /**
     * @throws ReflectionException if the component definition is invalid or can't be resolved.
     */
    public function testThrowExceptionWhenBehaviorIdNotString(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('\'Behavior class\': \'ID\' must be a \'string\', got \'integer\'.');

        new ServiceMap(self::BASE_PATH . 'behaviors-unsupported-id-not-string.php');
    }

    /**
     * @throws ReflectionException if the component definition is invalid or can't be resolved.
     */
    public function testThrowExceptionWhenBehaviorsNotArray(): void
    {
        $configPath = self::BASE_PATH . 'behaviors-unsupported-is-not-array.php';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Configuration file '{$configPath}' must contain a valid 'behaviors' 'array'.");

        new ServiceMap($configPath);
    }
}
