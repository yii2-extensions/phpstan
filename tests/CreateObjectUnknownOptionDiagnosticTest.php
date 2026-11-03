<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use FilesystemIterator;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use yii2\extensions\phpstan\rules\{CreateObjectInvalidOptionTypeRule, CreateObjectUnknownOptionRule};
use yii2\extensions\phpstan\tests\provider\CreateObjectUnknownOptionDiagnosticProvider;

use function array_keys;
use function bin2hex;
use function dirname;
use function fclose;
use function file_get_contents;
use function file_put_contents;
use function is_array;
use function is_dir;
use function is_int;
use function is_resource;
use function is_string;
use function json_decode;
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
use const JSON_THROW_ON_ERROR;
use const PHP_BINARY;

/**
 * Integration tests for {@see CreateObjectUnknownOptionRule} and {@see CreateObjectInvalidOptionTypeRule} beside
 * native diagnostics in a real PHPStan run.
 *
 * {@see CreateObjectUnknownOptionDiagnosticProvider} for test case data providers.
 */
final class CreateObjectUnknownOptionDiagnosticTest extends TestCase
{
    /**
     * Shared code fixture analyzed by every case, relative to the tests directory.
     */
    private const string FIXTURE = 'data/diagnostic/CreateObjectUnknownOptionDiagnostic.php';

    /**
     * Prefix of the temporary directory owned by each test.
     */
    private const string TEMPORARY_PREFIX = 'yii2-phpstan-create-object-diagnostic-';

    /**
     * Temporary directory owned by the current test, or an empty string before it is created.
     */
    private string $workDirectory = '';

    /**
     * @phpstan-param list<string> $rules
     * @phpstan-param list<array{int, string}> $expected
     */
    #[DataProviderExternal(CreateObjectUnknownOptionDiagnosticProvider::class, 'ruleSwitchProvider')]
    public function testReportRuleDiagnosticsOnlyWhenEnabledAndOnceBesideNativeOnes(
        array $rules,
        array $expected,
    ): void {
        $fixture = realpath(__DIR__ . DIRECTORY_SEPARATOR . self::FIXTURE);

        self::assertIsString(
            $fixture,
            'Fixture must exist.',
        );

        $config = __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'create-object-config.php';
        $switches = '';

        foreach ($rules as $rule) {
            $switches .= "\n            {$rule}: true";
        }

        $switches = $switches === '' ? '' : "\n        rules:{$switches}";
        $support = __DIR__ . DIRECTORY_SEPARATOR . 'support' . DIRECTORY_SEPARATOR;

        $this->writeFile(
            'phpstan.neon',
            <<<NEON
            includes:
                - {$this->neonPath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'extension.neon')}

            parameters:
                level: max
                paths:
                    - {$this->neonPath($fixture)}
                tmpDir: {$this->neonPath($this->path('tmp'))}
                bootstrapFiles:
                    - {$this->neonPath($support . 'bootstrap.php')}
                stubFiles:
                    - {$this->neonPath($support . 'application-classes.stub')}
                yii2:
                    config_path: {$this->neonPath($config)}{$switches}

            NEON,
        );

        $pipes = [];

        $process = proc_open(
            [
                PHP_BINARY,
                dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR
                    . 'phpstan',
                'analyse',
                '--configuration',
                $this->path('phpstan.neon'),
                '--error-format=json',
                '--memory-limit=-1',
                '--no-progress',
            ],
            [
                0 => ['pipe', 'r'],
                1 => ['file', $this->path('stdout.txt'), 'w'],
                2 => ['file', $this->path('stderr.txt'), 'w'],
            ],
            $pipes,
            $this->workDirectory,
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
        $report = json_decode($this->readFile('stdout.txt'), true, 512, JSON_THROW_ON_ERROR);

        self::assertIsArray(
            $report,
            'Report must be a JSON object.',
        );
        self::assertSame(
            1,
            $exitCode,
            "Analysis must report the diagnostics without crashing.\n" . $this->readFile('stderr.txt'),
        );
        self::assertSame(
            [],
            $report['errors'] ?? null,
            'No configuration or internal error must be reported.',
        );

        $files = is_array($report['files'] ?? null) ? $report['files'] : [];

        self::assertSame(
            [$fixture],
            array_keys($files),
            'Diagnostics must belong to the shared fixture only.',
        );

        $file = $files[$fixture] ?? null;
        $diagnostics = [];

        foreach (is_array($file) && is_array($file['messages'] ?? null) ? $file['messages'] : [] as $message) {
            if (is_array($message) && is_int($message['line'] ?? null) && is_string($message['identifier'] ?? null)) {
                $diagnostics[] = [$message['line'], $message['identifier']];
            }
        }

        self::assertSame(
            $expected,
            $diagnostics,
            'Lines and identifiers must match the observed diagnostics.',
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

        if (mkdir($workDirectory, 0o700) === false) {
            throw new RuntimeException(
                sprintf('Temporary directory "%s" could not be created.', $workDirectory),
            );
        }

        $this->workDirectory = $workDirectory;
    }

    protected function tearDown(): void
    {
        $workDirectory = $this->workDirectory;

        $this->workDirectory = '';

        $temporaryDirectory = realpath(sys_get_temp_dir());

        if (
            $workDirectory === ''
            || $temporaryDirectory === false
            || str_starts_with($workDirectory, $temporaryDirectory . DIRECTORY_SEPARATOR . self::TEMPORARY_PREFIX)
                === false
            || is_dir($workDirectory) === false
        ) {
            return;
        }

        $entries = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($workDirectory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        /** @var SplFileInfo $entry */
        foreach ($entries as $entry) {
            $entry->isDir() && $entry->isLink() === false
                ? rmdir($entry->getPathname())
                : unlink($entry->getPathname());
        }

        rmdir($workDirectory);
    }

    private function neonPath(string $path): string
    {
        return "'" . str_replace(['\\', "'"], ['/', "''"], $path) . "'";
    }

    private function path(string $name): string
    {
        return $this->workDirectory . DIRECTORY_SEPARATOR . $name;
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

    private function writeFile(string $name, string $contents): void
    {
        if (file_put_contents($this->path($name), $contents) === false) {
            throw new RuntimeException(
                sprintf('File "%s" could not be written.', $name),
            );
        }
    }
}
