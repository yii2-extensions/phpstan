<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

use function array_values;
use function bin2hex;
use function dirname;
use function fclose;
use function file_get_contents;
use function file_put_contents;
use function is_dir;
use function is_link;
use function is_resource;
use function mkdir;
use function proc_close;
use function proc_open;
use function random_bytes;
use function realpath;
use function rmdir;
use function sprintf;
use function str_replace;
use function str_starts_with;
use function sys_get_temp_dir;
use function unlink;

use const DIRECTORY_SEPARATOR;
use const PHP_BINARY;

/**
 * Owns a temporary work directory for the current test and runs processes whose output is redirected into it.
 *
 * The using test case declares the `TEMPORARY_PREFIX` constant. Only the work directory created by
 * {@see createOwnedWorkDirectory()} is ever removed, after checking that it carries that prefix under the resolved system
 * temporary directory and is not a link.
 */
trait OwnedWorkDirectoryTrait
{
    /**
     * Temporary directory owned by the current test, or an empty string before it is created.
     */
    private string $workDirectory = '';

    protected function tearDown(): void
    {
        $workDirectory = $this->workDirectory;

        $this->workDirectory = '';

        if (self::isOwnedDirectory($workDirectory, $workDirectory) === false) {
            return;
        }

        self::removeDirectory($workDirectory);
    }

    /**
     * Creates the owned work directory with the given subdirectories, relative to it.
     */
    private function createOwnedWorkDirectory(string ...$directories): void
    {
        $temporaryDirectory = realpath(sys_get_temp_dir());

        if ($temporaryDirectory === false) {
            throw new RuntimeException(
                'System temporary directory is not available.',
            );
        }

        $workDirectory = $temporaryDirectory . DIRECTORY_SEPARATOR . self::TEMPORARY_PREFIX . bin2hex(random_bytes(8));

        if (mkdir($workDirectory, 0o700) === false) {
            throw new RuntimeException(
                sprintf('Temporary directory "%s" could not be created.', $workDirectory),
            );
        }

        $this->workDirectory = $workDirectory;

        foreach ($directories as $directory) {
            if (mkdir($this->path($directory), 0o700, true) === false) {
                throw new RuntimeException(
                    sprintf('Temporary directory "%s" could not be created.', $this->path($directory)),
                );
            }
        }
    }

    /**
     * Returns whether the given path is the test's own temporary directory or a directory inside it.
     */
    private static function isOwnedDirectory(string $workDirectory, string $path): bool
    {
        $temporaryDirectory = realpath(sys_get_temp_dir());

        return $workDirectory !== ''
            && $temporaryDirectory !== false
            && str_starts_with($workDirectory, $temporaryDirectory . DIRECTORY_SEPARATOR . self::TEMPORARY_PREFIX)
            && ($path === $workDirectory || str_starts_with($path, $workDirectory . DIRECTORY_SEPARATOR))
            && is_link($path) === false
            && is_dir($path);
    }

    private function neonPath(string $path): string
    {
        return "'" . str_replace(['\\', "'"], ['/', "''"], $path) . "'";
    }

    private function path(string $name): string
    {
        return $this->workDirectory . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $name);
    }

    /**
     * Returns the command that runs `phpstan analyse` with the given configuration, JSON error output, no memory limit
     * and no progress bar.
     *
     * @param string ...$options Extra command line options, such as `-vvv`.
     *
     * @phpstan-return list<string>
     */
    private static function phpstanCommand(string $configuration, string ...$options): array
    {
        return [
            PHP_BINARY,
            dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR
                . 'phpstan',
            'analyse',
            '--configuration',
            $configuration,
            '--error-format=json',
            '--memory-limit=-1',
            '--no-progress',
            ...array_values($options),
        ];
    }

    private function readFile(string $name): string
    {
        $contents = file_get_contents($this->path($name));

        if ($contents === false) {
            throw new RuntimeException(
                sprintf('File "%s" could not be read.', $name),
            );
        }

        return $contents;
    }

    private static function removeDirectory(string $directory): void
    {
        $entries = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        /** @var SplFileInfo $entry */
        foreach ($entries as $entry) {
            $entry->isDir() && $entry->isLink() === false
                ? rmdir($entry->getPathname())
                : unlink($entry->getPathname());
        }

        rmdir($directory);
    }

    /**
     * Runs the command in the given directory, with standard output and error redirected to the given files of the work
     * directory, and returns its exit code and both outputs.
     *
     * @param list<string> $command Command and arguments, run without a shell.
     * @param string $stdout Standard output file, relative to the work directory.
     * @param string $stderr Standard error file, relative to the work directory.
     *
     * @phpstan-return array{exitCode: int, stdout: string, stderr: string}
     */
    private function runProcess(array $command, string $directory, string $stdout, string $stderr): array
    {
        $pipes = [];

        $process = proc_open(
            $command,
            [
                0 => ['pipe', 'r'],
                1 => ['file', $this->path($stdout), 'w'],
                2 => ['file', $this->path($stderr), 'w'],
            ],
            $pipes,
            $directory,
        );

        if (is_resource($process) === false) {
            throw new RuntimeException(
                sprintf('Process "%s" could not be started.', $command[0] ?? ''),
            );
        }

        if (isset($pipes[0])) {
            fclose($pipes[0]);
        }

        $exitCode = proc_close($process);

        return ['exitCode' => $exitCode, 'stdout' => $this->readFile($stdout), 'stderr' => $this->readFile($stderr)];
    }

    private function writeFile(string $name, string $contents): void
    {
        if (file_put_contents($this->path($name), $contents) === false) {
            throw new RuntimeException(
                sprintf('File "%s" could not be written.', $name),
            );
        }
    }
}
