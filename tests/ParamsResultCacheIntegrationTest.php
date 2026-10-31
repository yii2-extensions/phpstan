<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPUnit\Framework\TestCase;
use yii2\extensions\phpstan\ServiceMapResultCacheValueExtension;
use yii2\extensions\phpstan\tests\support\ResultCacheIntegrationTrait;

use function dirname;
use function sprintf;

use const DIRECTORY_SEPARATOR;

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
    use ResultCacheIntegrationTrait;

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

    /**
     * Returns the hashes of every input of the analysis except the params file: the generated NEON file, configuration
     * wrapper, consumers and control file, and the included extension configuration and stubs.
     *
     * @phpstan-return array<string, string>
     */
    private function stableInputHashes(): array
    {
        return $this->inputHashes(
            [
                'project/phpstan.neon',
                'project/config.php',
                'project/src/AppParamsConsumer.php',
                'project/src/Control.php',
                'project/src/ModuleParamsConsumer.php',
            ],
        );
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
