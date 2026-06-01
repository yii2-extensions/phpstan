<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPUnit\Framework\TestCase;
use ReflectionException;
use RuntimeException;
use yii2\extensions\phpstan\ServiceMap;

/**
 * Test suite for {@see ServiceMap} params resolution and validation logic.
 *
 * Validates correct extraction and retrieval of application params from configuration files, ensuring robust error
 * handling for invalid params structures.
 */
final class ServiceMapParamsTest extends TestCase
{
    /**
     * Base path for configuration files used in tests.
     */
    private const BASE_PATH = __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;

    /**
     * @throws ReflectionException if the component definition is invalid or can't be resolved.
     */
    public function testReturnEmptyParamsWhenConfigHasNoParams(): void
    {
        $serviceMap = new ServiceMap(self::BASE_PATH . 'phpstan-console-config.php');

        self::assertSame(
            [],
            $serviceMap->getParams(),
            'ServiceMap should return an empty array when no params are configured.',
        );
    }

    /**
     * @throws ReflectionException if the component definition is invalid or can't be resolved.
     */
    public function testReturnNumericKeyedParamsFromConfig(): void
    {
        $serviceMap = new ServiceMap(self::BASE_PATH . 'params-numeric-keys-config.php');

        self::assertSame(
            [
                0 => 'first',
                1 => 'second',
                10 => 'tenth',
                'adminEmail' => 'admin@example.com',
            ],
            $serviceMap->getParams(),
            'Numeric and list keys must be preserved.',
        );
    }

    /**
     * @throws ReflectionException if the component definition is invalid or can't be resolved.
     */
    public function testReturnParamsFromConfig(): void
    {
        $serviceMap = new ServiceMap(self::BASE_PATH . 'params-config.php');

        $params = $serviceMap->getParams();

        self::assertSame(
            [
                'turnstile.siteKey' => '',
                'adminEmail' => 'admin@example.com',
                'maxItems' => 100,
                'debugMode' => true,
                'ratio' => 1.5,
                'nullableParam' => null,
                'nested' => ['key1' => 'value1', 'key2' => 42],
                'tags' => ['php', 'yii2'],
            ],
            $params,
            'ServiceMap should return all params from configuration.',
        );
    }

    /**
     * @throws ReflectionException if the component definition is invalid or can't be resolved.
     */
    public function testThrowRuntimeExceptionWhenParamsIsNull(): void
    {
        $configPath = self::BASE_PATH . 'params-unsupported-is-null.php';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            "Configuration file '{$configPath}' must contain a valid 'params' 'array'.",
        );

        new ServiceMap($configPath);
    }

    /**
     * @throws ReflectionException if the component definition is invalid or can't be resolved.
     */
    public function testThrowRuntimeExceptionWhenParamsNotArray(): void
    {
        $configPath = self::BASE_PATH . 'params-unsupported-is-not-array.php';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            "Configuration file '{$configPath}' must contain a valid 'params' 'array'.",
        );

        new ServiceMap($configPath);
    }
}
