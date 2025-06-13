<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPUnit\Framework\TestCase;
use ReflectionException;
use RuntimeException;
use yii2\extensions\phpstan\ServiceMap;
use yii2\extensions\phpstan\tests\stub\MyActiveRecord;
use yii2\extensions\phpstan\tests\stub\User;

/**
 * Test suite for {@see ServiceMap} component resolution and definition behavior.
 *
 * Validates the correct mapping and retrieval of component classes and definitions from configuration files, ensuring
 * robust error handling for invalid or unsupported component structures.
 *
 * The tests cover scenarios including valid and invalid component IDs, class resolution, definition extraction, and
 * exception handling for misconfigured or malformed component arrays.
 *
 * Key features.
 * - Ensures compatibility with fixture-based configuration files.
 * - Resolves component class by ID for valid and initialized components.
 * - Retrieves component definitions by ID and class name.
 * - Returns `null` for non-existent or non-class component IDs.
 * - Throws exceptions for invalid component ID types and non-array component definitions.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class ServiceMapComponentTest extends TestCase
{
    /**
     * @throws ReflectionException if the component definition is invalid or can't be resolved.
     */
    public function testReturnComponentClassWhenCustomComponentValid(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $fixturePath = __DIR__ . "{$ds}fixture{$ds}config{$ds}config.php";
        $serviceMap = new ServiceMap($fixturePath);

        self::assertSame(
            MyActiveRecord::class,
            $serviceMap->getComponentClassById('customComponent'),
            'ServiceMap should resolve component id \'customComponent\' to \'MyActiveRecord::class\'.',
        );
    }

    /**
     * @throws ReflectionException if the component definition is invalid or can't be resolved.
     */
    public function testReturnComponentClassWhenCustomInitializedComponentValid(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $fixturePath = __DIR__ . "{$ds}fixture{$ds}config{$ds}config.php";
        $serviceMap = new ServiceMap($fixturePath);

        self::assertSame(
            MyActiveRecord::class,
            $serviceMap->getComponentClassById('customInitializedComponent'),
            'ServiceMap should resolve component id \'customInitializedComponent\' to \'MyActiveRecord::class\'.',
        );
    }

    /**
     * @throws ReflectionException if the component definition is invalid or can't be resolved.
     */
    public function testReturnComponentDefinitionWhenClassNameValid(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $fixturePath = __DIR__ . "{$ds}fixture{$ds}config{$ds}config.php";
        $serviceMap = new ServiceMap($fixturePath);

        self::assertSame(
            ['identityClass' => 'yii2\extensions\phpstan\tests\stub\User'],
            $serviceMap->getComponentDefinitionByClassName('yii\web\User'),
            'ServiceMap should return the component definition for \'yii\web\User\'.',
        );
    }

    /**
     * @throws ReflectionException if the component definition is invalid or can't be resolved.
     */
    public function testReturnComponentDefinitionWhenUserIdValid(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $fixturePath = __DIR__ . "{$ds}fixture{$ds}config{$ds}config.php";
        $serviceMap = new ServiceMap($fixturePath);

        self::assertSame(
            ['identityClass' => User::class],
            $serviceMap->getComponentDefinitionById('user'),
            'ServiceMap should return the component definition for \'user\'.',
        );
    }

    /**
     * @throws ReflectionException if the component definition is invalid or can't be resolved.
     */
    public function testReturnNullWhenComponentClassNonExistent(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $fixturePath = __DIR__ . "{$ds}fixture{$ds}config{$ds}config.php";
        $serviceMap = new ServiceMap($fixturePath);

        self::assertNull(
            $serviceMap->getComponentDefinitionByClassName('nonExistentComponent'),
            'ServiceMap should return \'`\' f`or a non-existent component class.',
        );
    }

    /**
     * @throws ReflectionException if the component definition is invalid or can't be resolved.
     */
    public function testReturnNullWhenComponentIdNonExistent(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $fixturePath = __DIR__ . "{$ds}fixture{$ds}config{$ds}config.php";
        $serviceMap = new ServiceMap($fixturePath);

        self::assertNull(
            $serviceMap->getComponentDefinitionById('nonExistentComponent'),
            'ServiceMap should return \'null\' for a non-existent component class.',
        );
    }

    /**
     * @throws ReflectionException if the component definition is invalid or can't be resolved.
     */
    public function testReturnNullWhenComponentIdNotClass(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $fixturePath = __DIR__ . "{$ds}fixture{$ds}config{$ds}config.php";
        $serviceMap = new ServiceMap($fixturePath);

        self::assertNull(
            $serviceMap->getComponentClassById('assetManager'),
            'ServiceMap should return \'null\' for \'assetManager\' component id as it is not a class but an array.',
        );
    }

    /**
     * @throws ReflectionException if the component definition is invalid or can't be resolved.
     */
    public function testThrowExceptionWhenComponentIdNotString(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $fixturePath = __DIR__ . "{$ds}fixture{$ds}config{$ds}components-unsupported-id-not-string.php";

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('\'Component\': ID must be a string, got \'integer\'.');

        new ServiceMap($fixturePath);
    }

    /**
     * @throws ReflectionException if the component definition is invalid or can't be resolved.
     */
    public function testThrowExceptionWhenComponentsNotArray(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $fixturePath = __DIR__ . "{$ds}fixture{$ds}config{$ds}components-unsupported-is-not-array.php";

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Configuration file '{$fixturePath}' must contain a valid 'components' 'array'.");

        new ServiceMap($fixturePath);
    }
}
