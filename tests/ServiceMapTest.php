<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPUnit\Framework\TestCase;
use ReflectionException;
use RuntimeException;
use yii\web\Application;
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

    /**
     * @throws ReflectionException if the component definition is invalid or can't be resolved.
     */
    public function testReturnApplicationTypeWhenConfigValid(): void
    {
        $serviceMap = new ServiceMap(self::BASE_PATH . 'phpstan-config.php');

        self::assertSame(
            Application::class,
            $serviceMap->getApplicationType(),
            'ServiceMap should resolve the application type to \'yii\web\Application\'.',
        );
    }

    /**
     * @throws ReflectionException if the component definition is invalid or can't be resolved.
     */
    public function testReturnApplicationTypeWhenConsoleConfigValid(): void
    {
        $serviceMap = new ServiceMap(self::BASE_PATH . 'phpstan-console-config.php');

        self::assertSame(
            \yii\console\Application::class,
            $serviceMap->getApplicationType(),
            'ServiceMap should resolve the application type to \'yii\console\Application\'.',
        );
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testThrowRuntimeExceptionWhenPHPStanApplicationTypeIsNotArray(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            '\'Application type\': \'phpstan.application_type\' must be a \'string\', got \'integer\'.',
        );

        new ServiceMap(self::BASE_PATH . 'phpstan-unsupported-type-array-invalid.php');
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testThrowRuntimeExceptionWhenPHPStanConfigIsNotArray(): void
    {
        $configPath = self::BASE_PATH . 'phpstan-unsupported-is-not-array.php';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Configuration file '{$configPath}' must contain a valid 'phpstan' 'array'.");

        new ServiceMap($configPath);
    }
}
