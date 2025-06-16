<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPUnit\Framework\TestCase;
use yii2\extensions\phpstan\ServiceMap;
use yii2\extensions\phpstan\StubFilesExtension;

/**
 * Test suite for {@see StubFilesExtension} stub file resolution logic.
 *
 * Verifies that the stub files returned by the extension match the expected configuration for a web application type.
 *
 * This test ensures that the stub file path is correctly resolved based on the provided service map configuration,
 * maintaining compatibility with PHPStan analysis for Yii-based projects.
 *
 * Key features.
 * - Asserts that the returned stub files array matches the expected result.
 * - Ensures correct integration with {@see ServiceMap}.
 * - Validates stub file path resolution for web application configuration.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class StubFilesExtensionTest extends TestCase
{
    public function testGetFilesReturnsExpectedStubFilesForWebApplicationType(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $fixturePath = __DIR__ . "{$ds}fixture{$ds}config.php";

        $serviceMap = new ServiceMap($fixturePath);

        $stubFilesExtension = new StubFilesExtension($serviceMap);
        $stubFiles = dirname(__DIR__) . "{$ds}stub{$ds}BaseYiiWeb.stub";

        self::assertSame(
            [$stubFiles],
            $stubFilesExtension->getFiles(),
            'Expected stub files to match the web application type configuration.',
        );
    }
}
