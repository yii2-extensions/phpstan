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
 *
 * Key features.
 * - Ensures compatibility with based configuration files for both web and console applications.
 * - Provides coverage for both default and alternative application types.
 * - Resolves an application type using the 'phpstan.application_type' key in configuration.
 * - Throws exceptions for invalid or unsupported application type configurations.
 * - Validates correct class-string is returned for each application type scenario.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class ServiceMapTest extends TestCase
{
    /**
     * @throws ReflectionException if the component definition is invalid or can't be resolved.
     */
    public function testReturnApplicationTypeWhenConfigValid(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $configPath = __DIR__ . "{$ds}config{$ds}config.php";
        $serviceMap = new ServiceMap($configPath);

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
        $ds = DIRECTORY_SEPARATOR;
        $configPath = __DIR__ . "{$ds}console{$ds}config{$ds}config.php";
        $serviceMap = new ServiceMap($configPath);

        self::assertSame(
            \yii\console\Application::class,
            $serviceMap->getApplicationType(),
            'ServiceMap should resolve the application type to \'yii\console\Application\'.',
        );
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testThrowExceptionWhenPHPStanApplicationTypeIsNotArray(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $configPath = __DIR__ . "{$ds}config{$ds}phpstan-unsupported-type-array-invalid.php";

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            '\'Application type\': \'phpstan.application_type\' must be a \'string\', got \'integer\'.',
        );

        new ServiceMap($configPath);
    }

    /**
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     */
    public function testThrowExceptionWhenPHPStanConfigIsNotArray(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $configPath = __DIR__ . "{$ds}config{$ds}phpstan-unsupported-is-not-array.php";

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Configuration file '{$configPath}' must contain a valid 'phpstan' 'array'.");

        new ServiceMap($configPath);
    }
}
