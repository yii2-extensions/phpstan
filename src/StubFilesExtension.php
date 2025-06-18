<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan;

use yii\base\Application;

/**
 * Provides stub file resolution for PHPStan analysis based on a Yii Application type.
 *
 * Determines the appropriate stub file to use for static analysis by inspecting the application type from the provided
 * {@see ServiceMap} instance.
 *
 * This enables PHPStan to reflect the correct set of Yii Application properties and methods for web, console, or base
 * application contexts.
 *
 * The class supports mapping of application types to their corresponding stub files and falls back to a default stub if
 * the application type is not explicitly mapped.
 *
 * Stub files are resolved from the `stub/` directory relative to the extension source.
 *
 * Key features:
 * - Maps Yii Application types to specific stub files for accurate static analysis.
 * - Provides a fallback stub for unknown or custom application types.
 * - Resolves stub file paths relative to the extension directory.
 * - Supports web, console, and base application contexts.
 *
 * @see ServiceMap for service and component map for Yii Application static analysis.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class StubFilesExtension implements \PHPStan\PhpDoc\StubFilesExtension
{
    private const APPLICATION_TYPE_STUBS = [
        Application::class => 'BaseYii.stub',
        \yii\console\Application::class => 'BaseYiiConsole.stub',
        \yii\web\Application::class => 'BaseYiiWeb.stub',
    ];

    private const DEFAULT_STUB = 'ApplicationWeb.stub';

    /**
     * @param ServiceMap $serviceMap Service and component map for Yii Application static analysis.
     */
    public function __construct(private readonly ServiceMap $serviceMap) {}

    /**
     * Retrieves the appropriate stub file path for PHPStan analysis based on the Yii Application type.
     *
     * Determines the stub file to use for static analysis by inspecting the application type provided by the
     * {@see ServiceMap} instance.
     *
     * This enables PHPStan to reflect the correct set of Yii Application properties and methods for web, console, or
     * base application contexts.
     *
     * @return string[] Array containing the absolute path to the resolved stub file for PHPStan analysis.
     */
    public function getFiles(): array
    {
        $stubsDirectory = $this->getStubsDirectory();
        $applicationType = $this->serviceMap->getApplicationType();

        $stubFileName = self::APPLICATION_TYPE_STUBS[$applicationType] ?? self::DEFAULT_STUB;
        $stubFilePath = $stubsDirectory . DIRECTORY_SEPARATOR . $stubFileName;

        return [$stubFilePath];
    }

    /**
     * Retrieves the absolute path to the stub files directory for PHPStan analysis.
     *
     * Resolves the path to the `stub/` directory relative to the extension source directory, ensuring that stub files
     * are correctly located regardless of the current working directory or environment.
     *
     * This method is used internally to construct absolute paths for stub file resolution in static analysis.
     *
     * @return string Absolute path to the stub files directory.
     */
    private function getStubsDirectory(): string
    {
        $ds = DIRECTORY_SEPARATOR;

        return dirname(__DIR__) . "{$ds}stub";
    }
}
