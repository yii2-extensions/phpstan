<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

use function array_filter;
use function array_values;
use function bin2hex;
use function count;
use function dirname;
use function fclose;
use function file_get_contents;
use function file_put_contents;
use function hash_file;
use function is_array;
use function is_dir;
use function is_file;
use function is_int;
use function is_link;
use function is_resource;
use function is_string;
use function json_decode;
use function mkdir;
use function preg_match_all;
use function proc_close;
use function proc_open;
use function random_bytes;
use function realpath;
use function rmdir;
use function sort;
use function sprintf;
use function str_replace;
use function str_starts_with;
use function strlen;
use function strpos;
use function substr;
use function sys_get_temp_dir;
use function unlink;

use const DIRECTORY_SEPARATOR;
use const JSON_THROW_ON_ERROR;
use const PHP_BINARY;

/**
 * Runs real PHPStan processes on a project generated in a test-owned temporary directory and normalizes their results,
 * for result cache integration tests.
 *
 * The using test case declares the `TEMPORARY_PREFIX` constant and writes `project/phpstan.neon`, whose analysed paths,
 * `tmpDir` and configuration point inside the work directory. Only the work directory created by
 * {@see createWorkDirectory()} is ever removed, after checking that it carries that prefix under the resolved system
 * temporary directory.
 */
trait ResultCacheIntegrationTrait
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
     * Runs PHPStan in a separate process and collects its error output and normalized results.
     *
     * Each result is the path relative to the project, the line, the identifier and, for `phpstan.dumpType`, the dumped
     * type, sorted for a stable comparison.
     *
     * @phpstan-return array{stderr: string, results: list<array{string, int, string, string|null}>}
     */
    private function analyse(): array
    {
        $pipes = [];

        $process = proc_open(
            [
                PHP_BINARY,
                dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR
                    . 'phpstan',
                'analyse',
                '--configuration',
                $this->path('project/phpstan.neon'),
                '--error-format=json',
                '--memory-limit=-1',
                '--no-progress',
                '-vvv',
            ],
            [
                0 => ['pipe', 'r'],
                1 => ['file', $this->path('output/stdout.txt'), 'w'],
                2 => ['file', $this->path('output/stderr.txt'), 'w'],
            ],
            $pipes,
            $this->path('project'),
        );

        if (is_resource($process) === false) {
            throw new RuntimeException(
                'PHPStan process could not be started.',
            );
        }

        if (isset($pipes[0])) {
            fclose($pipes[0]);
        }

        $exitCode = proc_close($process);
        $stderr = $this->readFile('output/stderr.txt');
        $report = $this->report($this->readFile('output/stdout.txt'));

        self::assertSame(
            1,
            $exitCode,
            "Analysis must report the dumped types without crashing.\n" . $stderr,
        );
        self::assertSame(
            [],
            $report['errors'],
            'No configuration or internal error must be reported.',
        );

        return ['stderr' => $stderr, 'results' => $report['results']];
    }

    /**
     * Returns the stub that declares the application classes for the validation of project stubs, since the extension
     * is included from this checkout rather than from `vendor/`.
     */
    private static function applicationClassesStub(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'application-classes.stub';
    }

    /**
     * Asserts that the run restored the result cache and reanalysed the given number of files.
     */
    private static function assertRestored(int $expected, string $stderr, string $run): void
    {
        $matches = [];

        $count = preg_match_all(
            '/Result cache restored(?: in [\d.]+ seconds)?\. (\d+) files? will be reanalysed\./',
            $stderr,
            $matches,
        );

        self::assertSame(
            1,
            $count,
            sprintf("%s must restore the result cache once.\n%s", $run, $stderr),
        );
        self::assertStringNotContainsString(
            'Result cache not used',
            $stderr,
            sprintf('%s must reuse the cached results, not only the `tmpDir`.', $run),
        );
        self::assertSame(
            (string) $expected,
            $matches[1][0],
            sprintf('%s must reanalyse %d files.', $run, $expected),
        );
    }

    /**
     * Creates the owned work directory with its `output` and `project/src` subdirectories.
     */
    private function createWorkDirectory(): void
    {
        $temporaryDirectory = realpath(sys_get_temp_dir());

        if ($temporaryDirectory === false) {
            throw new RuntimeException(
                'System temporary directory is not available.',
            );
        }

        $workDirectory = $temporaryDirectory . DIRECTORY_SEPARATOR . self::TEMPORARY_PREFIX . bin2hex(random_bytes(8));

        foreach (['output', 'project' . DIRECTORY_SEPARATOR . 'src'] as $directory) {
            if (mkdir($workDirectory . DIRECTORY_SEPARATOR . $directory, 0o700, true) === false) {
                throw new RuntimeException(
                    sprintf('Temporary directory "%s" could not be created.', $workDirectory),
                );
            }
        }

        $this->workDirectory = $workDirectory;
    }

    private static function hashFile(string $path): string
    {
        $hash = hash_file('sha256', $path);

        if ($hash === false) {
            throw new RuntimeException(
                sprintf('File "%s" could not be hashed.', $path),
            );
        }

        return $hash;
    }

    /**
     * Returns the hashes of the given generated project files and of the included extension configuration and stubs.
     *
     * @phpstan-param list<string> $projectFiles
     *
     * @phpstan-return array<string, string>
     */
    private function inputHashes(array $projectFiles): array
    {
        $root = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR;
        $inputs = [
            'extension.neon' => $root . 'extension.neon',
            'stubs/yii.stub' => $root . 'stubs' . DIRECTORY_SEPARATOR . 'yii.stub',
            'tests/support/application-classes.stub' => self::applicationClassesStub(),
        ];

        foreach ($projectFiles as $name) {
            $inputs[$name] = $this->path($name);
        }

        $hashes = [];

        foreach ($inputs as $name => $path) {
            self::assertTrue(
                is_file($path),
                sprintf('Stable input "%s" must exist.', $name),
            );

            $hashes[$name] = self::hashFile($path);
        }

        self::assertCount(
            3 + count($projectFiles),
            $hashes,
            'Every stable input must be hashed.',
        );

        return $hashes;
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

    /**
     * Returns the resolved path with forward slashes, or the given path when it can't be resolved.
     */
    private static function normalizedPath(string $path): string
    {
        $resolved = realpath($path);

        return str_replace('\\', '/', $resolved === false ? $path : $resolved);
    }

    private function path(string $name): string
    {
        return $this->workDirectory . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $name);
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
     * Removes the `tmpDir` the test created, after checking that it is the expected child of the owned directory.
     */
    private function removeTemporaryCacheDirectory(): void
    {
        $cacheDirectory = $this->path('tmp');

        if (self::isOwnedDirectory($this->workDirectory, $cacheDirectory) === false) {
            throw new RuntimeException(
                sprintf('Directory "%s" is not the test-owned `tmpDir`.', $cacheDirectory),
            );
        }

        self::removeDirectory($cacheDirectory);

        self::assertDirectoryDoesNotExist(
            $cacheDirectory,
            'Owned `tmpDir` must be removed before the final cold run.',
        );
    }

    /**
     * Extracts the non-file-specific errors and the normalized file results from the JSON error output, requiring every
     * reported file to belong to the generated project.
     *
     * @phpstan-return array{errors: list<string>, results: list<array{string, int, string, string|null}>}
     */
    private function report(string $stdout): array
    {
        $start = strpos($stdout, '{"totals"');

        if ($start === false) {
            throw new RuntimeException(
                sprintf('PHPStan did not print a JSON report: %s', $stdout),
            );
        }

        $report = json_decode(substr($stdout, $start), true, 512, JSON_THROW_ON_ERROR);

        $errors = [];

        foreach (is_array($report) && is_array($report['errors'] ?? null) ? $report['errors'] : [] as $error) {
            if (is_string($error)) {
                $errors[] = $error;
            }
        }

        $project = self::normalizedPath($this->path('project')) . '/';
        $results = [];

        foreach (is_array($report) && is_array($report['files'] ?? null) ? $report['files'] : [] as $file => $data) {
            $path = self::normalizedPath((string) $file);

            self::assertStringStartsWith(
                $project,
                $path,
                'Every reported file must belong to the generated project.',
            );

            $messages = is_array($data) && is_array($data['messages'] ?? null) ? $data['messages'] : [];

            foreach ($messages as $message) {
                if (
                    is_array($message) === false
                    || is_int($message['line'] ?? null) === false
                    || is_string($message['identifier'] ?? null) === false
                    || is_string($message['message'] ?? null) === false
                ) {
                    throw new RuntimeException(
                        sprintf('Unexpected message in the JSON report for "%s".', $path),
                    );
                }

                $results[] = [
                    substr($path, strlen($project)),
                    $message['line'],
                    $message['identifier'],
                    $message['identifier'] === 'phpstan.dumpType'
                        ? str_replace('Dumped type: ', '', $message['message'])
                        : null,
                ];
            }
        }

        sort($results);

        return ['errors' => $errors, 'results' => $results];
    }

    /**
     * Returns the results of one file.
     *
     * @phpstan-param list<array{string, int, string, string|null}> $results
     *
     * @phpstan-return list<array{string, int, string, string|null}>
     */
    private static function resultsOf(string $file, array $results): array
    {
        return array_values(array_filter($results, static fn(array $result): bool => $result[0] === $file));
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
