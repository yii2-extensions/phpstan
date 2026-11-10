<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use yii2\extensions\phpstan\rules\{CreateObjectInvalidOptionTypeRule, CreateObjectUnknownOptionRule};
use yii2\extensions\phpstan\tests\provider\CreateObjectUnknownOptionDiagnosticProvider;
use yii2\extensions\phpstan\tests\support\OwnedWorkDirectoryTrait;

use function array_keys;
use function dirname;
use function is_array;
use function is_int;
use function is_string;
use function json_decode;
use function realpath;
use function sprintf;

use const DIRECTORY_SEPARATOR;
use const JSON_THROW_ON_ERROR;

/**
 * Integration tests for {@see CreateObjectUnknownOptionRule} and {@see CreateObjectInvalidOptionTypeRule} beside
 * native diagnostics in a real PHPStan run.
 *
 * {@see CreateObjectUnknownOptionDiagnosticProvider} for test case data providers.
 */
final class CreateObjectUnknownOptionDiagnosticTest extends TestCase
{
    use OwnedWorkDirectoryTrait;

    /**
     * Shared code fixture analyzed by every case, relative to the tests directory.
     */
    private const string FIXTURE = 'data/diagnostic/CreateObjectUnknownOptionDiagnostic.php';

    /**
     * Prefix of the temporary directory owned by each test.
     */
    private const string TEMPORARY_PREFIX = 'yii2-phpstan-create-object-diagnostic-';

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

        $run = $this->runProcess(
            self::phpstanCommand($this->path('phpstan.neon')),
            $this->workDirectory,
            'stdout.txt',
            'stderr.txt',
        );

        self::assertSame(
            1,
            $run['exitCode'],
            "Analysis must report the diagnostics without crashing.\n" . $run['stderr'],
        );

        self::assertJson(
            $run['stdout'],
            "Analysis must print a JSON report.\n" . $run['stderr'],
        );

        $report = json_decode($run['stdout'], true, 512, JSON_THROW_ON_ERROR);

        self::assertIsArray(
            $report,
            'Report must be a JSON object.',
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
            if (
                is_array($message) === false
                || is_int($message['line'] ?? null) === false
                || is_string($message['identifier'] ?? null) === false
            ) {
                throw new RuntimeException(
                    sprintf('Unexpected message in the JSON report for "%s".', $fixture),
                );
            }

            $diagnostics[] = [$message['line'], $message['identifier']];
        }

        self::assertSame(
            $expected,
            $diagnostics,
            'Lines and identifiers must match the observed diagnostics.',
        );
    }

    protected function setUp(): void
    {
        $this->createOwnedWorkDirectory();
    }
}
