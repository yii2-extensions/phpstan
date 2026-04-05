<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan;

use RuntimeException;
use yii\base\Application;

use function array_is_list;
use function array_keys;
use function file_exists;
use function file_put_contents;
use function getmypid;
use function implode;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;
use function ltrim;
use function md5;
use function preg_match;
use function rename;
use function sprintf;
use function str_replace;
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
        $paramsProperty = $this->buildParamsPropertyDeclaration();

        $baseDeclaration = <<<PHP
        namespace yii\base {
            class Module
            {{$paramsProperty}
            }

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
     * Builds the `$params` property declaration for the stub file.
     *
     * Generates a PHPDoc `@var` annotation with an array shape type inferred from the configured application params.
     * Returns an empty string if no params are configured, allowing the native `array` type to remain unchanged.
     *
     * @return string PHP property declaration block with array shape annotation, or empty string if no params.
     */
    private function buildParamsPropertyDeclaration(): string
    {
        $params = $this->serviceMap->getParams();

        if ($params === []) {
            return '';
        }

        $typeString = $this->inferTypeString($params);

        return <<<PHP

                /**
                 * @var {$typeString}
                 */
                public \$params;
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
     * Formats an array key for use in a PHPStan array shape type annotation.
     *
     * Wraps keys containing non-identifier characters in single quotes to satisfy PHPStan type syntax.
     *
     * @param string $key Array key to format.
     *
     * @return string Formatted key, quoted if it contains special characters.
     */
    private function formatArrayKey(string $key): string
    {
        if (preg_match('/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/', $key) === 1) {
            return $key;
        }

        $escaped = str_replace(['\\', "'"], ['\\\\', "\\'"], $key);

        return "'{$escaped}'";
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

        /**
         * @codeCoverageIgnoreStart
         * atomic publish: rename within the same filesystem is atomic on POSIX. This fallback handles Windows (where
         * rename fails if target exists) and other non-POSIX edge cases during concurrent PHPStan runs.
         */
        if (!@rename($temporaryPath, $stubPath)) {
            @unlink($temporaryPath);

            if (file_exists($stubPath)) {
                return $stubPath;
            }

            throw new RuntimeException(
                sprintf("Failed to write stub file to '%s'. Ensure the temporary directory is writable.", $stubPath),
            );
        }
        /** @codeCoverageIgnoreEnd */

        return $stubPath;
    }

    /**
     * Infers a PHPStan array type string from a PHP array value.
     *
     * Builds an array shape type for associative arrays (recursive) and list arrays. Returns `array<mixed, mixed>` for
     * empty arrays.
     *
     * @param array<array-key, mixed> $value PHP array to infer a type string from.
     *
     * @return string PHPStan array type annotation string.
     */
    private function inferArrayType(array $value): string
    {
        if ($value === []) {
            return 'array<mixed, mixed>';
        }

        $allStringKeys = true;

        foreach (array_keys($value) as $k) {
            if (is_string($k) === false) {
                $allStringKeys = false;

                break;
            }
        }

        if ($allStringKeys) {
            $entries = [];

            foreach ($value as $k => $v) {
                /** @phpstan-var string $k */
                $entries[] = $this->formatArrayKey($k) . ': ' . $this->inferTypeString($v);
            }

            return 'array{' . implode(', ', $entries) . '}';
        }

        if (array_is_list($value)) {
            $entries = [];

            foreach ($value as $v) {
                $entries[] = $this->inferTypeString($v);
            }

            return 'array{' . implode(', ', $entries) . '}';
        }

        $entries = [];

        foreach ($value as $k => $v) {
            $key = is_int($k) ? (string) $k : $this->formatArrayKey($k);
            $entries[] = $key . ': ' . $this->inferTypeString($v);
        }

        return 'array{' . implode(', ', $entries) . '}';
    }

    /**
     * Infers a PHPStan scalar type string from a PHP value.
     *
     * Returns the type name for `null`, `string`, `int`, `float`, and `bool` values, or `null` if the value is not a
     * scalar type.
     *
     * @param mixed $value PHP value to check.
     *
     * @return string|null PHPStan type name, or `null` if not a recognized scalar.
     */
    private function inferScalarType(mixed $value): string|null
    {
        if ($value === null) {
            return 'null';
        }

        if (is_string($value)) {
            return 'string';
        }

        if (is_int($value)) {
            return 'int';
        }

        if (is_float($value)) {
            return 'float';
        }

        if (is_bool($value)) {
            return 'bool';
        }

        return null;
    }

    /**
     * Infers a PHPStan type string from a PHP value.
     *
     * Delegates to {@see inferScalarType()} for scalar values and {@see inferArrayType()} for arrays. Falls back to
     * `mixed` for unsupported value types.
     *
     * @param mixed $value PHP value to infer a type string from.
     *
     * @return string PHPStan type annotation string.
     */
    private function inferTypeString(mixed $value): string
    {
        $scalarType = $this->inferScalarType($value);

        if ($scalarType !== null) {
            return $scalarType;
        }

        if (is_array($value)) {
            return $this->inferArrayType($value);
        }

        return 'mixed';
    }
}
