<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan;

use RuntimeException;
use yii\base\Application;

use function file_exists;
use function file_put_contents;
use function getmypid;
use function ltrim;
use function md5;
use function rename;
use function sprintf;
use function strrpos;
use function substr;
use function sys_get_temp_dir;
use function unlink;

use const DIRECTORY_SEPARATOR;
use const LOCK_EX;

/**
 * Provides dynamic stub file generation for PHPStan analysis based on the configured Yii Application type.
 *
 * Generates a stub file at runtime that overrides the `Yii::$app` property type annotation to match the application
 * type specified in the project configuration. This enables PHPStan to infer the correct application class for web,
 * console, or custom application contexts without requiring separate static stub files.
 *
 * The generated stub is written atomically to a deterministic temporary file path, cached across PHPStan runs for the
 * same content.
 *
 * @see ServiceMap for service and component map for Yii Application static analysis.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class StubFilesExtension implements \PHPStan\PhpDoc\StubFilesExtension
{
    /**
     * @param ServiceMap $serviceMap Service and component map for Yii Application static analysis.
     * @param string $stubDirectory Directory for generated stub files (default: system temporary directory).
     */
    public function __construct(
        private readonly ServiceMap $serviceMap,
        private readonly string $stubDirectory = '',
    ) {}

    /**
     * Retrieves the dynamically generated stub file path for PHPStan analysis.
     *
     * Generates a stub file with the correct `@var` type annotation for `Yii::$app` based on the configured application
     * type from the {@see ServiceMap} instance.
     *
     * @return array Array containing the absolute path to the generated stub file for PHPStan analysis.
     *
     * @phpstan-return string[]
     */
    public function getFiles(): array
    {
        return [$this->generateStub($this->serviceMap->getApplicationType())];
    }

    /**
     * Builds the application type class declaration block for the stub.
     *
     * Generates the necessary namespace and class declarations to satisfy PHPStan stub type resolution for the
     * configured application type. Includes the base `\yii\base\Application` declaration and, if the configured type
     * differs, an additional declaration for the specific application class.
     *
     * @param string $applicationType Fully qualified class name of the application type (without leading backslash).
     *
     * @return string PHP namespace block declarations for the stub file.
     */
    private function buildApplicationTypeDeclaration(string $applicationType): string
    {
        $baseDeclaration = <<<PHP
        namespace yii\base {
            abstract class Application {}
        }
        PHP;

        if ($applicationType === Application::class) {
            return $baseDeclaration;
        }

        $lastSeparator = strrpos($applicationType, '\\');

        if ($lastSeparator === false) {
            $namespace = '';
            $className = $applicationType;
        } else {
            $namespace = substr($applicationType, 0, $lastSeparator);
            $className = substr($applicationType, $lastSeparator + 1);
        }

        $namespaceBlock = $namespace !== '' ? "namespace {$namespace}" : 'namespace';

        return <<<PHP
            {$baseDeclaration}

            {$namespaceBlock} {
                class {$className} extends \yii\base\Application {}
            }
            PHP;
    }

    /**
     * Builds the full stub content for the specified application type.
     *
     * Assembles the PHP stub content including class declarations for PHPStan stub type resolution, the `BaseYii`
     * class with the `$app` property type annotation, and the `Yii` class extending `BaseYii`.
     *
     * @param string $applicationType Fully qualified class name of the application type (without leading backslash).
     *
     * @return string Complete PHP stub file content.
     */
    private function buildStubContent(string $applicationType): string
    {
        $typeDeclaration = $this->buildApplicationTypeDeclaration($applicationType);

        return <<<PHP
        <?php

        {$typeDeclaration}

        namespace yii {
            class BaseYii
            {
                /**
                 * @var \\{$applicationType}
                 */
                public static \$app;
            }
        }

        namespace {
            class Yii extends \yii\BaseYii {}
        }
        PHP;
    }

    /**
     * Generates a stub file for the specified application type.
     *
     * Creates a PHP stub that overrides the `BaseYii::$app` property type annotation to match the configured
     * application type. Includes necessary class declarations for PHPStan stub type resolution. The cache key is
     * derived from the generated content, preventing stale files after generator changes. The stub is written
     * atomically using a temporary file and `rename()` to prevent concurrent PHPStan runs from reading half-written
     * files.
     *
     * @param string $applicationType Fully qualified class name of the application type.
     *
     * @throws RuntimeException If the stub file can't be written to the temporary directory.
     *
     * @return string Absolute path to the generated stub file.
     */
    private function generateStub(string $applicationType): string
    {
        $escapedType = ltrim($applicationType, '\\');

        $content = $this->buildStubContent($escapedType);

        $directory = $this->stubDirectory !== '' ? $this->stubDirectory : sys_get_temp_dir();
        $stubPath = $directory . DIRECTORY_SEPARATOR . 'yii2-phpstan-stub-' . md5($content) . '.stub';

        if (file_exists($stubPath)) {
            return $stubPath;
        }

        $temporaryPath = $stubPath . '.' . getmypid() . '.tmp';

        if (@file_put_contents($temporaryPath, $content, LOCK_EX) === false) {
            throw new RuntimeException(
                sprintf("Failed to write stub file to '%s'. Ensure the temporary directory is writable.", $stubPath),
            );
        }

        // @codeCoverageIgnoreStart
        // atomic publish: rename within the same filesystem is atomic on POSIX. This fallback handles Windows (where
        // rename fails if target exists) and other non-POSIX edge cases during concurrent PHPStan runs.
        if (!@rename($temporaryPath, $stubPath)) {
            @unlink($temporaryPath);

            if (file_exists($stubPath)) {
                return $stubPath;
            }

            throw new RuntimeException(
                sprintf("Failed to write stub file to '%s'. Ensure the temporary directory is writable.", $stubPath),
            );
        }
        // @codeCoverageIgnoreEnd

        return $stubPath;
    }
}
