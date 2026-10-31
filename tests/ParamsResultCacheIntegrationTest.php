<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use yii2\extensions\phpstan\ServiceMapResultCacheValueExtension;

use function array_filter;
use function array_values;
use function bin2hex;
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
 * Integration tests for the params value that {@see ServiceMapResultCacheValueExtension} exposes to the PHPStan result
 * cache, across cold and warm runs of a real PHPStan process.
 *
 * The test generates its own project in a temporary directory: a stable NEON file, a configuration wrapper that requires
 * a separate params file, one consumer of `yii\BaseYii::$app->params`, one consumer of `yii\base\Module::$params`, and a
 * control file that reads a local array shape without Yii. Only the params file changes during the sequence. Each run
 * reports file, line and identifier of every diagnostic, plus the type shown by `PHPStan\dumpType()`; messages are not
 * compared otherwise.
 */
final class ParamsResultCacheIntegrationTest extends TestCase
{
    /**
     * Expected diagnostics of the control file, which no params change may alter.
     */
    private const array CONTROL = [
        ['src/Control.php', 14, 'phpstan.dumpType', 'string'],
        ['src/Control.php', 15, 'offsetAccess.notFound', null],
        ['src/Control.php', 15, 'phpstan.dumpType', '*ERROR*'],
    ];

    /**
     * Prefix of the temporary directory owned by each test.
     */
    private const string TEMPORARY_PREFIX = 'yii2-phpstan-params-result-cache-';

    /**
     * Temporary directory owned by the current test, or an empty string before it is created.
     */
    private string $workDirectory = '';

    public function testReanalyseParamsConsumersOnlyWhenParamsShapeChanges(): void
    {
        $stableInputs = $this->stableInputHashes();

        $initial = [
            ['src/AppParamsConsumer.php', 11, 'phpstan.dumpType', 'string'],
            ['src/AppParamsConsumer.php', 12, 'offsetAccess.notFound', null],
            ['src/AppParamsConsumer.php', 12, 'phpstan.dumpType', '*ERROR*'],
            ...self::CONTROL,
            ['src/ModuleParamsConsumer.php', 11, 'phpstan.dumpType', 'string'],
            ['src/ModuleParamsConsumer.php', 12, 'offsetAccess.notFound', null],
            ['src/ModuleParamsConsumer.php', 12, 'phpstan.dumpType', '*ERROR*'],
        ];
        $changed = [
            ['src/AppParamsConsumer.php', 11, 'phpstan.dumpType', 'int'],
            ['src/AppParamsConsumer.php', 12, 'phpstan.dumpType', 'bool'],
            ...self::CONTROL,
            ['src/ModuleParamsConsumer.php', 11, 'phpstan.dumpType', 'int'],
            ['src/ModuleParamsConsumer.php', 12, 'phpstan.dumpType', 'bool'],
        ];

        $this->writeParams("['mode' => 'alpha']");

        $initialParams = self::hashFile($this->path('project/params.php'));
        $cold = $this->analyse();

        self::assertStringContainsString(
            'Result cache not used because the cache file does not exist.',
            $cold['stderr'],
            'Initial run must start without a result cache.',
        );
        self::assertStringContainsString(
            'Result cache is saved.',
            $cold['stderr'],
            'Initial run must write the result cache.',
        );
        self::assertFileExists(
            $this->path('tmp/resultCache.php'),
            'Result cache file must be created in the owned `tmpDir`.',
        );
        self::assertSame(
            $initial,
            $cold['results'],
            'Initial run must infer `string` and report the missing key.',
        );

        $warm = $this->analyse();

        self::assertRestored(
            0,
            $warm['stderr'],
            'Unchanged warm run',
        );
        self::assertStringContainsString(
            'Result cache was not rewritten because it is unchanged.',
            $warm['stderr'],
            'Unchanged warm run must keep the result cache as it is.',
        );
        self::assertSame(
            $initial,
            $warm['results'],
            'Unchanged warm run must restore the initial results.',
        );

        $this->writeParams("['mode' => 'beta']");

        $sameTypeParams = self::hashFile($this->path('project/params.php'));

        self::assertNotSame(
            $initialParams,
            $sameTypeParams,
            'Params file must change its value.',
        );

        $sameType = $this->analyse();

        self::assertRestored(
            0,
            $sameType['stderr'],
            'Same-type warm run',
        );
        self::assertSame(
            $initial,
            $sameType['results'],
            'A value of the same type must keep the initial results.',
        );

        $this->writeParams("['mode' => 42, 'extra' => true]");

        $changedParams = self::hashFile($this->path('project/params.php'));

        self::assertNotSame(
            $sameTypeParams,
            $changedParams,
            'Params file must change its shape.',
        );

        $shapeChanged = $this->analyse();

        self::assertRestored(
            2,
            $shapeChanged['stderr'],
            'Shape-changed warm run',
        );
        self::assertStringContainsString(
            'Result cache is saved.',
            $shapeChanged['stderr'],
            'Shape-changed warm run must save the new results.',
        );
        self::assertSame(
            $changed,
            $shapeChanged['results'],
            'Both params reads must see the new shape and the missing-key diagnostic must disappear.',
        );
        self::assertSame(
            self::CONTROL,
            self::resultsOf('src/Control.php', $shapeChanged['results']),
            'Control results must be unaffected by params.',
        );

        foreach (['src/AppParamsConsumer.php', 'src/ModuleParamsConsumer.php'] as $consumer) {
            self::assertNotSame(
                self::resultsOf($consumer, $initial),
                self::resultsOf($consumer, $shapeChanged['results']),
                sprintf('Results of "%s" must come from a new analysis, so it is one of the two files.', $consumer),
            );
        }

        $warmAfter = $this->analyse();

        self::assertRestored(
            0,
            $warmAfter['stderr'],
            'Warm run after the change',
        );
        self::assertSame(
            $changed,
            $warmAfter['results'],
            'Warm run after the change must restore the updated results.',
        );

        $this->removeTemporaryCacheDirectory();

        $coldFinal = $this->analyse();

        self::assertStringContainsString(
            'Result cache not used because the cache file does not exist.',
            $coldFinal['stderr'],
            'Final run must start without a result cache.',
        );
        self::assertSame(
            $shapeChanged['results'],
            $coldFinal['results'],
            'Final cold results must match the results after detecting the change.',
        );
        self::assertSame(
            $changedParams,
            self::hashFile($this->path('project/params.php')),
            'Final runs must use the changed params file.',
        );
        self::assertSame(
            $stableInputs,
            $this->stableInputHashes(),
            'Only the params file may change during the sequence.',
        );
    }

    protected function setUp(): void
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

        $this->writeFile(
            'project/phpstan.neon',
            <<<NEON
            includes:
                - {$this->neonPath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'extension.neon')}

            parameters:
                level: max
                paths:
                    - {$this->neonPath($this->path('project/src'))}
                tmpDir: {$this->neonPath($this->path('tmp'))}
                stubFiles:
                    - {$this->neonPath(self::applicationClassesStub())}
                yii2:
                    config_path: {$this->neonPath($this->path('project/config.php'))}

            NEON,
        );
        $this->writeFile(
            'project/config.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            return ['params' => require __DIR__ . '/params.php'];

            PHP,
        );
        $this->writeFile(
            'project/src/AppParamsConsumer.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Probe;

            final class AppParamsConsumer
            {
                public function read(): void
                {
                    \PHPStan\dumpType(\yii\BaseYii::$app->params['mode']);
                    \PHPStan\dumpType(\yii\BaseYii::$app->params['extra']);
                }
            }

            PHP,
        );
        $this->writeFile(
            'project/src/ModuleParamsConsumer.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Probe;

            final class ModuleParamsConsumer
            {
                public function read(\yii\base\Module $module): void
                {
                    \PHPStan\dumpType($module->params['mode']);
                    \PHPStan\dumpType($module->params['extra']);
                }
            }

            PHP,
        );
        $this->writeFile(
            'project/src/Control.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Probe;

            final class Control
            {
                /**
                 * @param array{mode: string} $values
                 */
                public function read(array $values): void
                {
                    \PHPStan\dumpType($values['mode']);
                    \PHPStan\dumpType($values['extra']);
                }
            }

            PHP,
        );
    }

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
     * Runs PHPStan in a separate process and collects its exit code, error output and normalized results.
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
                dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR
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
        return __DIR__ . DIRECTORY_SEPARATOR . 'support' . DIRECTORY_SEPARATOR . 'application-classes.stub';
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

    /**
     * Returns the hashes of every input of the analysis except the params file: the generated NEON file, configuration
     * wrapper, consumers and control file, and the included extension configuration and stubs.
     *
     * @phpstan-return array<string, string>
     */
    private function stableInputHashes(): array
    {
        $root = dirname(__DIR__) . DIRECTORY_SEPARATOR;
        $inputs = [
            'extension.neon' => $root . 'extension.neon',
            'stubs/yii.stub' => $root . 'stubs' . DIRECTORY_SEPARATOR . 'yii.stub',
            'tests/support/application-classes.stub' => self::applicationClassesStub(),
        ];

        foreach (
            [
                'project/phpstan.neon',
                'project/config.php',
                'project/src/AppParamsConsumer.php',
                'project/src/Control.php',
                'project/src/ModuleParamsConsumer.php',
            ] as $name
        ) {
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
            8,
            $hashes,
            'Every stable input must be hashed.',
        );

        return $hashes;
    }

    private function writeFile(string $name, string $contents): void
    {
        if (file_put_contents($this->path($name), $contents) === false) {
            throw new RuntimeException(
                sprintf('File "%s" could not be written.', $name),
            );
        }
    }

    private function writeParams(string $params): void
    {
        $this->writeFile(
            'project/params.php',
            <<<PHP
            <?php

            declare(strict_types=1);

            return {$params};

            PHP,
        );
    }
}
