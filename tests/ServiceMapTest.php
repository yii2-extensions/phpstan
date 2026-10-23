<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use yii\web\Application;
use yii2\extensions\phpstan\exception\Message;
use yii2\extensions\phpstan\ServiceMap;

/**
 * Test suite for {@see ServiceMap} application type resolution logic.
 *
 * Verifies correct detection and retrieval of the Yii application type from configuration files, ensuring accurate
 * mapping for static analysis and IDE integration.
 *
 * The tests cover scenarios including valid web and console application configurations, validating that the application
 * type is resolved as expected for different environments.
 */
final class ServiceMapTest extends TestCase
{
    /**
     * Base path for configuration files used in tests.
     */
    private const BASE_PATH = __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;

    public function testReturnApplicationTypeWhenConfigValid(): void
    {
        $serviceMap = new ServiceMap(self::BASE_PATH . 'phpstan-config.php');

        self::assertSame(
            Application::class,
            $serviceMap->getApplicationType(),
            "ServiceMap should resolve the application type to 'yii\web\Application'.",
        );
    }

    public function testReturnApplicationTypeWhenConsoleConfigValid(): void
    {
        $serviceMap = new ServiceMap(self::BASE_PATH . 'phpstan-console-config.php');

        self::assertSame(
            \yii\console\Application::class,
            $serviceMap->getApplicationType(),
            "ServiceMap should resolve the application type to 'yii\console\Application'.",
        );
    }

    public function testThrowRuntimeExceptionWhenPHPStanApplicationTypeIsNotArray(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            Message::VALUE_NOT_STRING->getMessage('Application type', 'phpstan.application_type', 'integer'),
        );

        new ServiceMap(self::BASE_PATH . 'phpstan-unsupported-type-array-invalid.php');
    }

    public function testThrowRuntimeExceptionWhenPHPStanConfigIsNotArray(): void
    {
        $configPath = self::BASE_PATH . 'phpstan-unsupported-is-not-array.php';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            Message::CONFIG_SECTION_NOT_ARRAY->getMessage($configPath, 'phpstan'),
        );

        new ServiceMap($configPath);
    }
}
