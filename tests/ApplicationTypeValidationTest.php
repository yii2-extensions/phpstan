<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use yii2\extensions\phpstan\exception\Message;
use yii2\extensions\phpstan\reflection\ApplicationClassResolver;
use yii2\extensions\phpstan\tests\provider\ApplicationTypeValidationProvider;
use yii2\extensions\phpstan\tests\support\OwnedWorkDirectoryTrait;

use function dirname;
use function implode;
use function is_array;
use function is_string;
use function json_decode;
use function preg_match;
use function sprintf;
use function str_replace;
use function strpos;
use function substr;
use function var_export;

use const DIRECTORY_SEPARATOR;
use const JSON_THROW_ON_ERROR;

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
    use OwnedWorkDirectoryTrait;

    /**
     * Prefix of the temporary directory owned by each test.
     */
    private const string TEMPORARY_PREFIX = 'yii2-phpstan-application-type-';

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
        $this->createOwnedWorkDirectory('lib', 'src', 'stubs');

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

        $run = $this->runProcess(
            self::phpstanCommand($this->path('phpstan.neon'), '-vvv'),
            $this->workDirectory,
            'stdout.txt',
            'stderr.txt',
        );
        $report = self::report($run['stdout']);

        return [
            'exitCode' => $run['exitCode'],
            'errors' => $report['errors'],
            'stderr' => $run['stderr'],
            'types' => $report['types'],
        ];
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
}
