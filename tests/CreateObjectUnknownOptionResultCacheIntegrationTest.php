<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use yii2\extensions\phpstan\rules\CreateObjectUnknownOptionRule;
use yii2\extensions\phpstan\tests\provider\CreateObjectUnknownOptionResultCacheIntegrationProvider;
use yii2\extensions\phpstan\tests\support\ResultCacheIntegrationTrait;

use function array_diff_assoc;
use function dirname;
use function explode;
use function sprintf;
use function str_replace;
use function time;

use const DIRECTORY_SEPARATOR;

/**
 * Integration tests for {@see CreateObjectUnknownOptionRule} result cache dependencies across cold and warm runs.
 *
 * {@see CreateObjectUnknownOptionResultCacheIntegrationProvider} for test case data providers.
 */
final class CreateObjectUnknownOptionResultCacheIntegrationTest extends TestCase
{
    use ResultCacheIntegrationTrait;

    /**
     * Expected diagnostics of the consumer of `Probe\Target` while the option is unknown.
     */
    private const array CHANGING_CONSUMER = [
        ['src/ChangingConsumer.php', 11, 'yii2.createObject.unknownOption', null],
    ];

    /**
     * Expected diagnostics of the control file, which no change may alter.
     */
    private const array CONTROL = [
        ['src/Control.php', 14, 'phpstan.dumpType', 'string'],
        ['src/Control.php', 15, 'offsetAccess.notFound', null],
        ['src/Control.php', 15, 'phpstan.dumpType', '*ERROR*'],
    ];

    /**
     * Expected diagnostics of the consumer of `Probe\Other`, which no change may alter.
     */
    private const array STABLE_CONSUMER = [
        ['src/StableConsumer.php', 11, 'yii2.createObject.unknownOption', null],
    ];

    /**
     * Created class whose container binding the binding test adds or removes.
     */
    private const string TARGET = 'Probe\Target';

    /**
     * Prefix of the temporary directory owned by each test.
     */
    private const string TEMPORARY_PREFIX = 'yii2-phpstan-create-object-result-cache-';

    #[DataProviderExternal(CreateObjectUnknownOptionResultCacheIntegrationProvider::class, 'bindingProvider')]
    public function testReanalyseConsumerWhenContainerBindingOfCreatedClassChanges(
        string $initialId,
        string $changedId,
    ): void {
        $this->writeDefinitions($initialId);

        $stableInputs = $this->stableInputHashes(['project/src/Target.php']);

        $unbound = [...self::CHANGING_CONSUMER, ...self::CONTROL, ...self::STABLE_CONSUMER];
        $bound = [...self::CONTROL, ...self::STABLE_CONSUMER];
        $before = $initialId === self::TARGET ? $bound : $unbound;
        $after = $changedId === self::TARGET ? $bound : $unbound;

        $initialDefinitions = $this->readFile('project/definitions.php');
        $initialHash = self::hashFile($this->path('project/definitions.php'));

        $coldStartedAt = time();

        $initialCold = $this->analyse();

        $this->assertInitialColdRun($initialCold['stderr'], $initialCold['results'], $before);

        $this->assertFirstWarmRunUnchanged(
            $this->analyse(),
            $before,
            $coldStartedAt,
            'Unchanged initial warm run',
        );

        $this->writeDefinitions($changedId);

        $changedHash = self::hashFile($this->path('project/definitions.php'));

        self::assertNotSame(
            $initialHash,
            $changedHash,
            'Definitions file must change its contents.',
        );
        self::assertSame(
            [6 => sprintf("    '%s' => ['class' => 'Probe\\\\Target'],", str_replace('\\', '\\\\', $changedId))],
            array_diff_assoc(
                explode("\n", $this->readFile('project/definitions.php')),
                explode("\n", $initialDefinitions),
            ),
            'Only the bound ID may differ.',
        );

        $changed = $this->analyse();

        self::assertRestored(
            1,
            $changed['stderr'],
            'Changed warm run',
        );
        self::assertStringContainsString(
            'Result cache is saved.',
            $changed['stderr'],
            'Changed warm run must save the new results.',
        );
        self::assertSame(
            $after,
            $changed['results'],
            'Changing consumer must follow the binding: stand aside while bound, report the option while unbound.',
        );

        $changedWarm = $this->analyse();

        self::assertRestored(
            0,
            $changedWarm['stderr'],
            'Unchanged changed warm run',
        );
        self::assertStringContainsString(
            'Result cache was not rewritten because it is unchanged.',
            $changedWarm['stderr'],
            'Unchanged changed warm run must keep the result cache as it is.',
        );
        self::assertSame(
            $after,
            $changedWarm['results'],
            'Unchanged changed warm run must restore the changed results.',
        );

        $this->assertFinalColdRun($changed['results']);

        self::assertSame(
            $changedHash,
            self::hashFile($this->path('project/definitions.php')),
            'Final runs must use the changed definitions.',
        );
        self::assertSame(
            $stableInputs,
            $this->stableInputHashes(['project/src/Target.php']),
            'Only the definitions file may change during the sequence.',
        );
    }

    public function testReanalyseConsumerWhenCreatedClassGainsSetter(): void
    {
        $stableInputs = $this->stableInputHashes(['project/definitions.php']);

        $before = [...self::CHANGING_CONSUMER, ...self::CONTROL, ...self::STABLE_CONSUMER];
        $after = [...self::CONTROL, ...self::STABLE_CONSUMER];

        $initialTarget = $this->readFile('project/src/Target.php');
        $initialHash = self::hashFile($this->path('project/src/Target.php'));

        $coldStartedAt = time();

        $initialCold = $this->analyse();

        $this->assertInitialColdRun($initialCold['stderr'], $initialCold['results'], $before);

        $this->assertFirstWarmRunUnchanged(
            $this->analyse(),
            $before,
            $coldStartedAt,
            'Unchanged initial warm run',
        );

        $this->writeTarget(true);

        $changedHash = self::hashFile($this->path('project/src/Target.php'));

        self::assertNotSame(
            $initialHash,
            $changedHash,
            'Target class must change its contents.',
        );
        self::assertSame(
            str_replace("}\n", "\n    public function setColour(string \$colour): void {}\n}\n", $initialTarget),
            $this->readFile('project/src/Target.php'),
            'Only the added setter may differ.',
        );

        $changeStartedAt = time();

        $changed = $this->analyse();

        self::assertRestored(
            2,
            $changed['stderr'],
            'Changed warm run',
        );
        self::assertStringContainsString(
            'Result cache is saved.',
            $changed['stderr'],
            'Changed warm run must save the new results.',
        );
        self::assertSame(
            $after,
            $changed['results'],
            'Changing consumer must lose its stale diagnostic.',
        );

        $this->assertFirstWarmRunUnchanged(
            $this->analyse(),
            $after,
            $changeStartedAt,
            'Unchanged changed warm run',
        );

        $this->assertFinalColdRun($changed['results']);

        self::assertSame(
            $changedHash,
            self::hashFile($this->path('project/src/Target.php')),
            'Final runs must use the changed class.',
        );
        self::assertSame(
            $stableInputs,
            $this->stableInputHashes(['project/definitions.php']),
            'Only the target class may change during the sequence.',
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
                    rules:
                        createObjectUnknownOption: true

            NEON,
        );
        // params keep project stub validation from reporting the plain `Module::$params` array at level max
        $this->writeFile(
            'project/config.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            return [
                'container' => ['definitions' => require __DIR__ . '/definitions.php'],
                'params' => ['mode' => 'alpha'],
            ];

            PHP,
        );
        $this->writeDefinitions('Probe\Placeholder');
        $this->writeTarget(false);
        $this->writeFile(
            'project/src/Other.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Probe;

            class Other extends \yii\base\BaseObject
            {
                public string $name = '';
            }

            PHP,
        );

        foreach (['ChangingConsumer' => 'Probe\Target', 'StableConsumer' => 'Probe\Other'] as $consumer => $class) {
            $this->writeFile(
                "project/src/{$consumer}.php",
                <<<PHP
                <?php

                declare(strict_types=1);

                namespace Probe;

                final class {$consumer}
                {
                    public function create(): void
                    {
                        \\yii\\BaseYii::createObject(['class' => '{$class}', 'colour' => 'red']);
                    }
                }

                PHP,
            );
        }

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
     * Removes the result cache, runs PHPStan cold and asserts that it reports the results of the changed run.
     *
     * @phpstan-param list<array{string, int, string, string|null}> $expected
     */
    private function assertFinalColdRun(array $expected): void
    {
        $this->removeTemporaryCacheDirectory();

        $finalCold = $this->analyse();

        self::assertStringContainsString(
            'Result cache not used because the cache file does not exist.',
            $finalCold['stderr'],
            'Final run must start without a result cache.',
        );
        self::assertSame(
            $expected,
            $finalCold['results'],
            'Final cold results must match the results after detecting the change.',
        );
    }

    /**
     * Asserts that the initial run started without a result cache, wrote it, and reported the initial results.
     *
     * @phpstan-param list<array{string, int, string, string|null}> $results
     * @phpstan-param list<array{string, int, string, string|null}> $expected
     */
    private function assertInitialColdRun(string $stderr, array $results, array $expected): void
    {
        self::assertStringContainsString(
            'Result cache not used because the cache file does not exist.',
            $stderr,
            'Initial run must start without a result cache.',
        );
        self::assertStringContainsString(
            'Result cache is saved.',
            $stderr,
            'Initial run must write the result cache.',
        );
        self::assertFileExists(
            $this->path('tmp/resultCache.php'),
            'Result cache file must be created in the owned `tmpDir`.',
        );
        self::assertSame(
            $expected,
            $results,
            'Initial run must report the unknown option of both consumers.',
        );
    }

    /**
     * Returns the hashes of every analysis input except the changing one.
     *
     * @phpstan-param list<string> $extra Unchanged inputs specific to the test method.
     *
     * @phpstan-return array<string, string>
     */
    private function stableInputHashes(array $extra): array
    {
        return $this->inputHashes(
            [
                'project/phpstan.neon',
                'project/config.php',
                'project/src/ChangingConsumer.php',
                'project/src/Control.php',
                'project/src/Other.php',
                'project/src/StableConsumer.php',
                ...$extra,
            ],
        );
    }

    /**
     * Writes the container definitions with `Probe\Target` bound under the given ID.
     */
    private function writeDefinitions(string $boundId): void
    {
        $id = "'" . str_replace('\\', '\\\\', $boundId) . "'";

        $this->writeFile(
            'project/definitions.php',
            <<<PHP
            <?php

            declare(strict_types=1);

            return [
                'cache.stable' => 'yii\\\\caching\\\\DummyCache',
                {$id} => ['class' => 'Probe\\\\Target'],
            ];

            PHP,
        );
    }

    /**
     * Writes `Probe\Target`, optionally with a setter for the `colour` option.
     */
    private function writeTarget(bool $withSetter): void
    {
        $setter = $withSetter ? "\n    public function setColour(string \$colour): void {}\n" : '';

        $this->writeFile(
            'project/src/Target.php',
            <<<PHP
            <?php

            declare(strict_types=1);

            namespace Probe;

            class Target extends \\yii\\base\\BaseObject
            {
                public string \$name = '';
            {$setter}}

            PHP,
        );
    }
}
