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
 * - Ensures compatibility with the provided configuration files.
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
     * Base path for configuration files used in tests.
     */
    private const BASE_PATH = __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;

    /**
     * @throws ReflectionException if the component definition is invalid or can't be resolved.
     */
    public function testReturnComponentClassWhenCustomComponentValid(): void
    {
        $serviceMap = new ServiceMap(self::BASE_PATH . 'phpstan-config.php');

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
        $serviceMap = new ServiceMap(self::BASE_PATH . 'phpstan-config.php');

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
        $serviceMap = new ServiceMap(self::BASE_PATH . 'phpstan-config.php');

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
        $serviceMap = new ServiceMap(self::BASE_PATH . 'phpstan-config.php');

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
        $serviceMap = new ServiceMap(self::BASE_PATH . 'phpstan-config.php');

        self::assertNull(
            $serviceMap->getComponentDefinitionByClassName('nonExistentComponent'),
            'ServiceMap should return \'null\' for a \'nonExistentComponent\' class.',
        );
    }

    /**
     * @throws ReflectionException if the component definition is invalid or can't be resolved.
     */
    public function testReturnNullWhenComponentIdNonExistent(): void
    {
        $serviceMap = new ServiceMap(self::BASE_PATH . 'phpstan-config.php');

        self::assertSame(
            [],
            $serviceMap->getComponentDefinitionById('nonExistentComponent'),
            'ServiceMap should return an empty array for a \'nonExistentComponent\' id.',
        );
    }

    /**
     * @throws ReflectionException if the component definition is invalid or can't be resolved.
     */
    public function testReturnNullWhenComponentIdNotClass(): void
    {
        $serviceMap = new ServiceMap(self::BASE_PATH . 'phpstan-config.php');

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
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('\'Component\': \'ID\' must be a \'string\', got \'integer\'.');

        new ServiceMap(self::BASE_PATH . 'components-unsupported-id-not-string.php');
    }

    /**
     * @throws ReflectionException if the component definition is invalid or can't be resolved.
     */
    public function testThrowExceptionWhenComponentsNotArray(): void
    {
        $configPath = self::BASE_PATH . 'components-unsupported-is-not-array.php';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Configuration file '{$configPath}' must contain a valid 'components' 'array'.");

        new ServiceMap($configPath);
    }
}
