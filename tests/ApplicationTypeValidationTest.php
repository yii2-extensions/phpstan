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
use yii2\extensions\phpstan\exception\Message;
use yii2\extensions\phpstan\reflection\ApplicationClassResolver;
use yii2\extensions\phpstan\tests\provider\ApplicationTypeValidationProvider;

use function bin2hex;
use function dirname;
use function fclose;
use function file_get_contents;
use function file_put_contents;
use function implode;
use function is_array;
use function is_dir;
use function is_resource;
use function is_string;
use function json_decode;
use function mkdir;
use function preg_match;
use function proc_close;
use function proc_open;
use function random_bytes;
use function realpath;
use function rmdir;
use function sprintf;
use function str_replace;
use function str_starts_with;
use function strpos;
use function substr;
use function sys_get_temp_dir;
use function unlink;
use function var_export;

use const DIRECTORY_SEPARATOR;
use const JSON_THROW_ON_ERROR;
use const PHP_BINARY;

/**
 * Integration tests for the validation of `phpstan.application_type` by {@see ApplicationClassResolver} in a real
 * PHPStan run.
 *
 * Each case runs PHPStan in a separate process against files generated in its own temporary directory, with its own
 * result cache. The generated project declares application classes reachable only through one discovery source each:
 * a file the configuration requires, an analysed path, a bootstrap file, and a stub file. The probe reads
 * `yii\BaseYii::$app`, the property the shipped stub declares, since global `Yii` is autoloadable only on Yii 22.
 *
 * {@see ApplicationTypeValidationProvider} for test case data providers.
 */
final class ApplicationTypeValidationTest extends TestCase
{
    /**
     * Prefix of the temporary directory owned by each test.
     */
    private const string TEMPORARY_PREFIX = 'yii2-phpstan-application-type-';

    /**
     * Temporary directory owned by the current test, or an empty string before it is created.
     */
    private string $workDirectory = '';

    /**
     * @phpstan-param list<string> $expected
     */
    #[DataProviderExternal(ApplicationTypeValidationProvider::class, 'acceptedApplicationTypeProvider')]
    public function testAcceptApplicationTypeFromEachDiscoverySource(
        string|null $applicationType,
        array $expected,
    ): void {
        $result = $this->analyse($applicationType);

        self::assertSame(
            1,
            $result['exitCode'],
            "Analysis must report the dumped types without crashing.\n" . $result['stderr'],
        );
        self::assertSame(
            [],
            $result['errors'],
            'No configuration or internal error must be reported.',
        );
        self::assertSame(
            $expected,
            $result['types'],
            'Application and request types must come from the configured class.',
        );
    }

    public function testReanalyseDependentsWhenSwitchingBetweenValidApplicationTypes(): void
    {
        $web = $this->analyse('yii\web\Application');

        self::assertSame(
            ['yii\web\Application', 'yii\web\Request'],
            $web['types'],
            'Cold run must infer the Web application type.',
        );

        $console = $this->analyse('yii\console\Application');

        self::assertSame(
            1,
            preg_match('/Result cache restored\. [1-9]\d* files? will be reanalysed\./', $console['stderr']),
            "Warm run must reanalyse the dependents of the application type.\n" . $console['stderr'],
        );
        self::assertSame(
            [],
            $console['errors'],
            'No configuration or internal error must be reported.',
        );
        self::assertSame(
            ['yii\console\Application', 'yii\console\Request'],
            $console['types'],
            'Warm run must infer the console application type.',
        );
    }

    #[DataProviderExternal(ApplicationTypeValidationProvider::class, 'rejectedApplicationTypeProvider')]
    public function testRejectApplicationTypeWithConfigurationError(string $applicationType, string $kind): void
    {
        $result = $this->analyse($applicationType);

        self::assertSame(
            1,
            $result['exitCode'],
            "Analysis must fail without crashing.\n" . $result['stderr'],
        );
        self::assertStringContainsString(
            Message::APPLICATION_TYPE_NOT_APPLICATION_CLASS->getMessage($applicationType, $kind),
            implode("\n", $result['errors']),
            'Configuration error must name the value and the expected class.',
        );
        self::assertSame(
            [],
            $result['types'],
            'No type must be inferred from the rejected application type.',
        );
        self::assertStringContainsString(
            'Result cache was not saved',
            $result['stderr'],
            'Rejected configuration must not be cached.',
        );
    }

    public function testRejectInvalidApplicationTypeAfterValidResultCache(): void
    {
        $valid = $this->analyse('yii\web\Application');

        self::assertSame(
            ['yii\web\Application', 'yii\web\Request'],
            $valid['types'],
            'Cold run must infer the valid application type.',
        );
        self::assertStringContainsString(
            'Result cache is saved',
            $valid['stderr'],
            'Valid cold run must write the result cache.',
        );

        $message = Message::APPLICATION_TYPE_NOT_APPLICATION_CLASS->getMessage(
            'Probe\MissingApplication',
            'an unknown class',
        );

        foreach (['first', 'second'] as $run) {
            $invalid = $this->analyse('Probe\MissingApplication');

            self::assertSame(
                1,
                $invalid['exitCode'],
                "Analysis must fail without crashing.\n" . $invalid['stderr'],
            );
            self::assertSame(
                1,
                preg_match('/Result cache restored\. [1-9]\d* files? will be reanalysed\./', $invalid['stderr']),
                sprintf('Warm %s run must reanalyse the dependents of the application type.', $run),
            );
            self::assertStringContainsString(
                $message,
                implode("\n", $invalid['errors']),
                sprintf('Warm %s run must report the configuration error.', $run),
            );
            self::assertSame(
                [],
                $invalid['types'],
                sprintf('Warm %s run must not reuse the cached types.', $run),
            );
            self::assertStringContainsString(
                'Result cache was not saved',
                $invalid['stderr'],
                sprintf('Warm %s run must keep the previous result cache.', $run),
            );
        }
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

        foreach (['lib', 'src', 'stubs'] as $directory) {
            if (mkdir($workDirectory . DIRECTORY_SEPARATOR . $directory, 0o700, true) === false) {
                throw new RuntimeException(
                    sprintf('Temporary directory "%s" could not be created.', $workDirectory),
                );
            }
        }

        $this->workDirectory = $workDirectory;

        $this->writeFile(
            'bootstrap.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            final class BootstrapApplication extends \yii\web\Application {}

            PHP,
        );
        $this->writeFile(
            'lib' . DIRECTORY_SEPARATOR . 'ConfigRequiredApplication.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            final class ConfigRequiredApplication extends \yii\web\Application {}

            PHP,
        );
        $this->writeFile(
            'src' . DIRECTORY_SEPARATOR . 'AnalysedApplication.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Probe;

            final class AnalysedApplication extends \yii\web\Application {}

            enum AnalysedEnum
            {
                case Value;
            }

            PHP,
        );
        $this->writeFile(
            'src' . DIRECTORY_SEPARATOR . 'Probe.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Probe;

            function application(): void
            {
                \PHPStan\dumpType(\yii\BaseYii::$app);
            }

            function request(\yii\base\Application $app): void
            {
                \PHPStan\dumpType($app->request);
            }

            PHP,
        );
        $this->writeFile(
            'stubs' . DIRECTORY_SEPARATOR . 'StubApplication.stub',
            <<<'PHP'
            <?php

            final class StubApplication extends \yii\web\Application {}

            PHP,
        );
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

    /**
     * Writes the configuration for the given application type, runs PHPStan in a separate process, and collects its
     * exit code, non-file-specific errors, dumped types and error output.
     *
     * @param string|null $applicationType Configured application type, or `null` to omit the key.
     *
     * @phpstan-return array{exitCode: int, errors: list<string>, stderr: string, types: list<string>}
     */
    private function analyse(string|null $applicationType): array
    {
        $phpstan = $applicationType === null
            ? ''
            : "'phpstan' => ['application_type' => " . var_export($applicationType, true) . '],';

        $this->writeFile(
            'config.php',
            <<<PHP
            <?php

            declare(strict_types=1);

            require_once __DIR__ . '/lib/ConfigRequiredApplication.php';

            return [{$phpstan}];

            PHP,
        );
        $this->writeFile(
            'phpstan.neon',
            <<<NEON
            includes:
                - {$this->neonPath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'extension.neon')}

            parameters:
                level: 0
                paths:
                    - {$this->neonPath($this->path('src'))}
                tmpDir: {$this->neonPath($this->path('tmp'))}
                bootstrapFiles:
                    - {$this->neonPath($this->path('bootstrap.php'))}
                stubFiles:
                    - {$this->neonPath($this->path('stubs' . DIRECTORY_SEPARATOR . 'StubApplication.stub'))}
                yii2:
                    config_path: {$this->neonPath($this->path('config.php'))}

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
                '-vvv',
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
        $report = self::report($this->readFile('stdout.txt'));

        return [
            'exitCode' => $exitCode,
            'errors' => $report['errors'],
            'stderr' => $this->readFile('stderr.txt'),
            'types' => $report['types'],
        ];
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

    /**
     * Extracts the non-file-specific errors and the types reported by `PHPStan\dumpType()` from the JSON error output.
     *
     * @phpstan-return array{errors: list<string>, types: list<string>}
     */
    private static function report(string $stdout): array
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

        $types = [];

        foreach (is_array($report) && is_array($report['files'] ?? null) ? $report['files'] : [] as $file) {
            $messages = is_array($file) && is_array($file['messages'] ?? null) ? $file['messages'] : [];

            foreach ($messages as $message) {
                if (
                    is_array($message)
                    && ($message['identifier'] ?? null) === 'phpstan.dumpType'
                    && is_string($message['message'] ?? null)
                ) {
                    $types[] = str_replace('Dumped type: ', '', $message['message']);
                }
            }
        }

        return ['errors' => $errors, 'types' => $types];
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
