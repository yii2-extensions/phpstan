<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPUnit\Framework\TestCase;
use yii2\extensions\phpstan\ServiceMapResultCacheValueExtension;
use yii2\extensions\phpstan\tests\support\ResultCacheIntegrationTrait;

use function dirname;
use function sprintf;
use function str_starts_with;
use function var_export;

use const DIRECTORY_SEPARATOR;

/**
 * Integration tests for the application value that {@see ServiceMapResultCacheValueExtension} exposes to the PHPStan
 * result cache, across cold and warm runs of a real PHPStan process.
 *
 * The test generates its own project in a temporary directory: a stable NEON file, a configuration wrapper that requires
 * a separate file returning only `phpstan.application_type` and a stable params file, consumers of
 * `yii\BaseYii::$app`, of its `request` component and of the `response` component on a `yii\base\Application`
 * receiver, a consumer of `yii\base\Module::$params`, and a control file that reads a local array shape without Yii.
 * Only the application selector changes during the sequence, from Web to console. Each run reports file, line and
 * identifier of every diagnostic, plus the type shown by `PHPStan\dumpType()`; messages are not compared otherwise.
 */
final class ApplicationTypeResultCacheIntegrationTest extends TestCase
{
    use ResultCacheIntegrationTrait;

    /**
     * Files whose results depend on the configured application class.
     */
    private const array APPLICATION_CONSUMERS = [
        'src/ApplicationConsumer.php',
        'src/RequestConsumer.php',
        'src/ResponseConsumer.php',
    ];

    /**
     * Expected diagnostics of the control file, which no application change may alter.
     */
    private const array CONTROL = [
        ['src/Control.php', 14, 'phpstan.dumpType', 'string'],
        ['src/Control.php', 15, 'offsetAccess.notFound', null],
        ['src/Control.php', 15, 'phpstan.dumpType', '*ERROR*'],
    ];

    /**
     * Expected diagnostics of the params consumer, which depends on Yii but not on the application class.
     */
    private const array PARAMS_CONSUMER = [
        ['src/ParamsConsumer.php', 11, 'phpstan.dumpType', 'string'],
    ];

    /**
     * Prefix of the temporary directory owned by each test.
     */
    private const string TEMPORARY_PREFIX = 'yii2-phpstan-application-result-cache-';

    public function testReanalyseApplicationConsumersOnlyWhenApplicationTypeChanges(): void
    {
        $stableInputs = $this->stableInputHashes();

        $web = [
            ['src/ApplicationConsumer.php', 11, 'phpstan.dumpType', 'yii\web\Application'],
            ...self::CONTROL,
            ...self::PARAMS_CONSUMER,
            ['src/RequestConsumer.php', 11, 'phpstan.dumpType', 'yii\web\Request'],
            ['src/RequestConsumer.php', 12, 'phpstan.dumpType', 'bool'],
            ['src/RequestConsumer.php', 13, 'method.notFound', null],
            ['src/RequestConsumer.php', 13, 'phpstan.dumpType', '*ERROR*'],
            ['src/ResponseConsumer.php', 11, 'phpstan.dumpType', 'yii\web\Response'],
            ['src/ResponseConsumer.php', 12, 'phpstan.dumpType', 'string'],
        ];
        $console = [
            ['src/ApplicationConsumer.php', 11, 'phpstan.dumpType', 'yii\console\Application'],
            ...self::CONTROL,
            ...self::PARAMS_CONSUMER,
            ['src/RequestConsumer.php', 11, 'phpstan.dumpType', 'yii\console\Request'],
            ['src/RequestConsumer.php', 12, 'method.notFound', null],
            ['src/RequestConsumer.php', 12, 'phpstan.dumpType', '*ERROR*'],
            ['src/RequestConsumer.php', 13, 'phpstan.dumpType', 'array'],
            ['src/ResponseConsumer.php', 11, 'phpstan.dumpType', 'yii\console\Response'],
            ['src/ResponseConsumer.php', 12, 'phpstan.dumpType', '*ERROR*'],
            ['src/ResponseConsumer.php', 12, 'property.notFound', null],
        ];

        $this->writeApplicationType('yii\web\Application');

        $webSelector = self::hashFile($this->path('project/application.php'));
        $webCold = $this->analyse();

        self::assertStringContainsString(
            'Result cache not used because the cache file does not exist.',
            $webCold['stderr'],
            'Initial run must start without a result cache.',
        );
        self::assertStringContainsString(
            'Result cache is saved.',
            $webCold['stderr'],
            'Initial run must write the result cache.',
        );
        self::assertFileExists(
            $this->path('tmp/resultCache.php'),
            'Result cache file must be created in the owned `tmpDir`.',
        );
        self::assertSame(
            $web,
            $webCold['results'],
            'Initial run must infer the Web application, its components and their diagnostics.',
        );

        $webWarm = $this->analyse();

        self::assertRestored(
            0,
            $webWarm['stderr'],
            'Unchanged Web warm run',
        );
        self::assertStringContainsString(
            'Result cache was not rewritten because it is unchanged.',
            $webWarm['stderr'],
            'Unchanged Web warm run must keep the result cache as it is.',
        );
        self::assertSame(
            $web,
            $webWarm['results'],
            'Unchanged Web warm run must restore the initial results.',
        );

        $this->writeApplicationType('yii\console\Application');

        $consoleSelector = self::hashFile($this->path('project/application.php'));

        self::assertNotSame(
            $webSelector,
            $consoleSelector,
            'Application selector must change its value.',
        );

        $switched = $this->analyse();

        self::assertRestored(
            3,
            $switched['stderr'],
            'Switched warm run',
        );
        self::assertStringContainsString(
            'Result cache is saved.',
            $switched['stderr'],
            'Switched warm run must save the new results.',
        );
        self::assertSame(
            $console,
            $switched['results'],
            'Every application consumer must see the console application and lose its Web diagnostics.',
        );
        self::assertSame(
            self::CONTROL,
            self::resultsOf('src/Control.php', $switched['results']),
            'Control results must be unaffected by the application class.',
        );
        self::assertSame(
            self::PARAMS_CONSUMER,
            self::resultsOf('src/ParamsConsumer.php', $switched['results']),
            'Params consumer results must be unaffected by the application class.',
        );

        foreach (self::APPLICATION_CONSUMERS as $consumer) {
            self::assertNotSame(
                self::resultsOf($consumer, $web),
                self::resultsOf($consumer, $switched['results']),
                sprintf('Results of "%s" must come from a new analysis, so it is one of the three files.', $consumer),
            );
        }

        foreach ($switched['results'] as $result) {
            self::assertFalse(
                $result[3] !== null && str_starts_with($result[3], 'yii\web\\'),
                sprintf('No Web type must remain after the switch, found "%s" in "%s".', $result[3], $result[0]),
            );
        }

        $consoleWarm = $this->analyse();

        self::assertRestored(
            0,
            $consoleWarm['stderr'],
            'Unchanged console warm run',
        );
        self::assertStringContainsString(
            'Result cache was not rewritten because it is unchanged.',
            $consoleWarm['stderr'],
            'Unchanged console warm run must keep the result cache as it is.',
        );
        self::assertSame(
            $console,
            $consoleWarm['results'],
            'Unchanged console warm run must restore the switched results.',
        );

        $this->removeTemporaryCacheDirectory();

        $consoleCold = $this->analyse();

        self::assertStringContainsString(
            'Result cache not used because the cache file does not exist.',
            $consoleCold['stderr'],
            'Final run must start without a result cache.',
        );
        self::assertSame(
            $switched['results'],
            $consoleCold['results'],
            'Final cold results must match the results after detecting the switch.',
        );
        self::assertSame(
            $consoleSelector,
            self::hashFile($this->path('project/application.php')),
            'Final runs must use the console application selector.',
        );
        self::assertSame(
            $stableInputs,
            $this->stableInputHashes(),
            'Only the application selector may change during the sequence.',
        );
    }

    protected function setUp(): void
    {
        $this->createWorkDirectory();

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

            return [
                'phpstan' => ['application_type' => require __DIR__ . '/application.php'],
                'params' => require __DIR__ . '/params.php',
            ];

            PHP,
        );
        $this->writeFile(
            'project/params.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            return ['mode' => 'alpha'];

            PHP,
        );
        $this->writeFile(
            'project/src/ApplicationConsumer.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Probe;

            final class ApplicationConsumer
            {
                public function read(): void
                {
                    \PHPStan\dumpType(\yii\BaseYii::$app);
                }
            }

            PHP,
        );
        $this->writeFile(
            'project/src/RequestConsumer.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Probe;

            final class RequestConsumer
            {
                public function read(): void
                {
                    \PHPStan\dumpType(\yii\BaseYii::$app->request);
                    \PHPStan\dumpType(\yii\BaseYii::$app->request->getIsAjax());
                    \PHPStan\dumpType(\yii\BaseYii::$app->request->getParams());
                }
            }

            PHP,
        );
        $this->writeFile(
            'project/src/ResponseConsumer.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Probe;

            final class ResponseConsumer
            {
                public function read(\yii\base\Application $app): void
                {
                    \PHPStan\dumpType($app->response);
                    \PHPStan\dumpType($app->response->format);
                }
            }

            PHP,
        );
        $this->writeFile(
            'project/src/ParamsConsumer.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Probe;

            final class ParamsConsumer
            {
                public function read(\yii\base\Module $module): void
                {
                    \PHPStan\dumpType($module->params['mode']);
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

    /**
     * Returns the hashes of every input of the analysis except the application selector: the generated NEON file,
     * configuration wrapper, params file, consumers and control file, and the included extension configuration and stubs.
     *
     * @phpstan-return array<string, string>
     */
    private function stableInputHashes(): array
    {
        return $this->inputHashes(
            [
                'project/phpstan.neon',
                'project/config.php',
                'project/params.php',
                'project/src/ApplicationConsumer.php',
                'project/src/Control.php',
                'project/src/ParamsConsumer.php',
                'project/src/RequestConsumer.php',
                'project/src/ResponseConsumer.php',
            ],
        );
    }

    /**
     * Writes the application selector, the only file that changes during the sequence.
     */
    private function writeApplicationType(string $applicationType): void
    {
        $value = var_export($applicationType, true);

        $this->writeFile(
            'project/application.php',
            <<<PHP
            <?php

            declare(strict_types=1);

            return {$value};

            PHP,
        );
    }
}
