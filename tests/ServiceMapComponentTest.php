<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use yii2\extensions\phpstan\ServiceMap;
use yii2\extensions\phpstan\tests\support\stub\{MyActiveRecord, User};

/**
 * Test suite for {@see ServiceMap} component resolution and definition behavior.
 *
 * Validates the correct mapping and retrieval of component classes and definitions from configuration files, ensuring
 * robust error handling for invalid or unsupported component structures.
 *
 * The tests cover scenarios including valid and invalid component IDs, class resolution, definition extraction, and
 * exception handling for misconfigured or malformed component arrays.
 */
final class ServiceMapComponentTest extends TestCase
{
    /**
     * Base path for configuration files used in tests.
     */
    private const BASE_PATH = __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;

    public function testReturnComponentClassWhenCustomComponentValid(): void
    {
        $serviceMap = new ServiceMap(self::BASE_PATH . 'phpstan-config.php');

        self::assertSame(
            MyActiveRecord::class,
            $serviceMap->getComponentClassById('customComponent'),
            "ServiceMap should resolve component id 'customComponent' to 'MyActiveRecord::class'.",
        );
    }

    public function testReturnComponentClassWhenCustomInitializedComponentValid(): void
    {
        $serviceMap = new ServiceMap(self::BASE_PATH . 'phpstan-config.php');

        self::assertSame(
            MyActiveRecord::class,
            $serviceMap->getComponentClassById('customInitializedComponent'),
            "ServiceMap should resolve component id 'customInitializedComponent' to 'MyActiveRecord::class'.",
        );
    }

    public function testReturnComponentDefinitionWhenUserIdValid(): void
    {
        $serviceMap = new ServiceMap(self::BASE_PATH . 'phpstan-config.php');

        self::assertSame(
            ['identityClass' => User::class],
            $serviceMap->getComponentDefinitionById('user'),
            "ServiceMap should return the component definition for 'user'.",
        );
    }

    public function testReturnNullWhenComponentIdNonExistent(): void
    {
        $serviceMap = new ServiceMap(self::BASE_PATH . 'phpstan-config.php');

        self::assertSame(
            [],
            $serviceMap->getComponentDefinitionById('nonExistentComponent'),
            "ServiceMap should return an empty array for a 'nonExistentComponent' id.",
        );
    }

    public function testReturnNullWhenComponentIdNotClass(): void
    {
        $serviceMap = new ServiceMap(self::BASE_PATH . 'phpstan-config.php');

        self::assertNull(
            $serviceMap->getComponentClassById('assetManager'),
            "ServiceMap should return 'null' for 'assetManager' component id as it is not a class but an array.",
        );
    }

    public function testThrowRuntimeExceptionWhenComponentIdNotString(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            "'Component': 'ID' must be a 'string', got 'integer'.",
        );

        new ServiceMap(self::BASE_PATH . 'components-unsupported-id-not-string.php');
    }

    public function testThrowRuntimeExceptionWhenComponentNotArray(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            "Unsupported definition for 'unsupported-type-integer'.",
        );

        new ServiceMap(self::BASE_PATH . 'components-unsupported-type-integer.php');
    }

    public function testThrowRuntimeExceptionWhenComponentsNotArray(): void
    {
        $configPath = self::BASE_PATH . 'components-unsupported-is-not-array.php';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            "Configuration file '{$configPath}' must contain a valid 'components' 'array'.",
        );

        new ServiceMap($configPath);
    }
}
