<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use yii2\extensions\phpstan\rules\{CreateObjectInvalidOptionTypeRule, CreateObjectUnknownOptionRule};
use yii2\extensions\phpstan\tests\provider\CreateObjectContainerBindingResultCacheIntegrationProvider;
use yii2\extensions\phpstan\tests\support\ResultCacheIntegrationTrait;

use function dirname;
use function implode;
use function str_replace;

use const DIRECTORY_SEPARATOR;

/**
 * Integration tests for the container construction dependencies of {@see CreateObjectUnknownOptionRule} and
 * {@see CreateObjectInvalidOptionTypeRule} across cold and warm result cache runs.
 *
 * {@see CreateObjectContainerBindingResultCacheIntegrationProvider} for test case data providers.
 */
final class CreateObjectContainerBindingResultCacheIntegrationTest extends TestCase
{
    use ResultCacheIntegrationTrait;

    /**
     * Expected diagnostics of the consumer of `Probe\Contract` while the container builds `Probe\Target` for it.
     */
    private const array BINDING_CONSUMER = [
        ['src/BindingConsumer.php', 11, 'yii2.createObject.invalidOptionType', null],
        ['src/BindingConsumer.php', 11, 'yii2.createObject.unknownOption', null],
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
     * Expected diagnostics of the consumer of the unbound `Probe\Other`, which no change may alter.
     */
    private const array STABLE_CONSUMER = [
        ['src/StableConsumer.php', 11, 'yii2.createObject.unknownOption', null],
    ];

    /**
     * Prefix of the temporary directory owned by each test.
     */
    private const string TEMPORARY_PREFIX = 'yii2-phpstan-create-object-binding-result-cache-';

    #[DataProviderExternal(CreateObjectContainerBindingResultCacheIntegrationProvider::class, 'classProvider')]
    public function testFollowClassChangeOfDefinitionWithOptions(
        string $definition,
        string $initialMembers,
        string $changedMembers,
        int $reanalysed,
        bool $initialReported,
        bool $changedReported,
    ): void {
        $this->writeContainer([$definition], []);
        $this->writeTarget(false, $initialMembers);

        $stableInputs = $this->inputHashes(
            [
                'project/phpstan.neon',
                'project/config.php',
                'project/container.php',
                'project/src/BindingConsumer.php',
                'project/src/Contract.php',
                'project/src/Control.php',
                'project/src/Other.php',
                'project/src/StableConsumer.php',
            ],
        );

        $before = self::results($initialReported);
        $after = self::results($changedReported);

        $initialTarget = $this->readFile('project/src/Target.php');

        $initialCold = $this->analyse();

        $this->assertInitialColdRun($initialCold['stderr'], $initialCold['results'], $before);

        $this->assertWarmRunUnchanged(
            $this->analyse(),
            $before,
            'Unchanged initial warm run',
        );

        $this->writeTarget(false, $changedMembers);

        self::assertSame(
            str_replace($initialMembers, $changedMembers, $initialTarget),
            $this->readFile('project/src/Target.php'),
            'Only the given members may differ.',
        );

        $changed = $this->analyse();

        self::assertRestored(
            $reanalysed,
            $changed['stderr'],
            'Changed warm run',
        );
        self::assertSame(
            $after,
            $changed['results'],
            'Binding consumer must report only while every definition option is a plain public property.',
        );

        $this->assertWarmRunUnchanged(
            $this->analyse(),
            $after,
            'Unchanged changed warm run',
        );

        $this->assertFinalColdRun($changed['results']);

        self::assertSame(
            $stableInputs,
            $this->inputHashes(
                [
                    'project/phpstan.neon',
                    'project/config.php',
                    'project/container.php',
                    'project/src/BindingConsumer.php',
                    'project/src/Contract.php',
                    'project/src/Control.php',
                    'project/src/Other.php',
                    'project/src/StableConsumer.php',
                ],
            ),
            'Only the target class may change during the sequence.',
        );
    }

    public function testReanalyseConsumerWhenClassBuiltThroughAliasGainsSetter(): void
    {
        $this->writeContainer(["'Probe\\Contract' => 'probe.hop',", "'probe.hop' => 'Probe\\Target',"], []);

        $stableInputs = $this->inputHashes(
            [
                'project/phpstan.neon',
                'project/config.php',
                'project/container.php',
                'project/src/BindingConsumer.php',
                'project/src/Contract.php',
                'project/src/Control.php',
                'project/src/Other.php',
                'project/src/StableConsumer.php',
            ],
        );

        $before = self::results(true);

        $after = [
            ['src/BindingConsumer.php', 11, 'yii2.createObject.invalidOptionType', null],
            ...self::CONTROL,
            ...self::STABLE_CONSUMER,
        ];

        $initialTarget = $this->readFile('project/src/Target.php');

        $initialCold = $this->analyse();

        $this->assertInitialColdRun($initialCold['stderr'], $initialCold['results'], $before);

        $this->assertWarmRunUnchanged(
            $this->analyse(),
            $before,
            'Unchanged initial warm run',
        );

        $this->writeTarget(true);

        self::assertSame(
            str_replace("}\n", "\n    public function setColour(string \$colour): void {}\n}\n", $initialTarget),
            $this->readFile('project/src/Target.php'),
            'Only the added setter may differ.',
        );

        $changed = $this->analyse();

        self::assertRestored(
            2,
            $changed['stderr'],
            'Changed warm run',
        );
        self::assertSame(
            $after,
            $changed['results'],
            'Binding consumer must lose its stale unknown option diagnostic.',
        );

        $this->assertWarmRunUnchanged(
            $this->analyse(),
            $after,
            'Unchanged changed warm run',
        );

        $this->assertFinalColdRun($changed['results']);

        self::assertSame(
            $stableInputs,
            $this->inputHashes(
                [
                    'project/phpstan.neon',
                    'project/config.php',
                    'project/container.php',
                    'project/src/BindingConsumer.php',
                    'project/src/Contract.php',
                    'project/src/Control.php',
                    'project/src/Other.php',
                    'project/src/StableConsumer.php',
                ],
            ),
            'Only the target class may change during the sequence.',
        );
    }

    /**
     * @phpstan-param list<string> $initialDefinitions
     * @phpstan-param list<string> $initialSingletons
     * @phpstan-param list<string> $changedDefinitions
     * @phpstan-param list<string> $changedSingletons
     */
    #[DataProviderExternal(CreateObjectContainerBindingResultCacheIntegrationProvider::class, 'definitionProvider')]
    public function testReanalyseConsumerWhenContainerChainChanges(
        array $initialDefinitions,
        array $initialSingletons,
        array $changedDefinitions,
        array $changedSingletons,
        bool $initialReported,
        bool $changedReported,
    ): void {
        $this->writeContainer($initialDefinitions, $initialSingletons);

        $stableInputs = $this->stableInputHashes();

        $before = self::results($initialReported);
        $after = self::results($changedReported);

        $initialHash = self::hashFile($this->path('project/container.php'));

        $initialCold = $this->analyse();

        $this->assertInitialColdRun($initialCold['stderr'], $initialCold['results'], $before);

        $this->assertWarmRunUnchanged(
            $this->analyse(),
            $before,
            'Unchanged initial warm run',
        );

        $this->writeContainer($changedDefinitions, $changedSingletons);

        $changedHash = self::hashFile($this->path('project/container.php'));

        self::assertNotSame(
            $initialHash,
            $changedHash,
            'Container file must change its contents.',
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
            'Binding consumer must follow the chain: report while it proves the class, stand aside otherwise.',
        );

        $changedWarm = $this->analyse();

        self::assertRestored(
            0,
            $changedWarm['stderr'],
            'Unchanged changed warm run',
        );
        self::assertSame(
            $after,
            $changedWarm['results'],
            'Unchanged changed warm run must restore the changed results.',
        );

        $this->assertFinalColdRun($changed['results']);

        self::assertSame(
            $changedHash,
            self::hashFile($this->path('project/container.php')),
            'Final runs must use the changed container.',
        );
        self::assertSame(
            $stableInputs,
            $this->stableInputHashes(),
            'Only the container file may change during the sequence.',
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
                        createObjectInvalidOptionType: true
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
                'container' => require __DIR__ . '/container.php',
                'params' => ['mode' => 'alpha'],
            ];

            PHP,
        );
        $this->writeTarget(false);
        $this->writeFile(
            'project/src/Contract.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Probe;

            interface Contract {}

            PHP,
        );
        $this->writeFile(
            'project/src/Other.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Probe;

            class Other extends \yii\base\BaseObject
            {
                public string $colour = '';

                public mixed $name = null;
            }

            PHP,
        );

        $consumers = [
            'BindingConsumer' => "'Probe\\Contract', 'colour' => 'red', 'name' => []",
            'StableConsumer' => "'Probe\\Other', 'size' => 1",
        ];

        foreach ($consumers as $consumer => $options) {
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
                        \\yii\\BaseYii::createObject(['class' => {$options}]);
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
            'Initial run must report the initial results.',
        );
    }

    /**
     * Returns the expected results, with the binding consumer's diagnostics when the chain proves `Probe\Target`.
     *
     * @phpstan-return list<array{string, int, string, string|null}>
     */
    private static function results(bool $reported): array
    {
        return [
            ...($reported ? self::BINDING_CONSUMER : []),
            ...self::CONTROL,
            ...self::STABLE_CONSUMER,
        ];
    }

    /**
     * Returns the hashes of every analysis input except the container file.
     *
     * @phpstan-return array<string, string>
     */
    private function stableInputHashes(): array
    {
        return $this->inputHashes(
            [
                'project/phpstan.neon',
                'project/config.php',
                'project/src/BindingConsumer.php',
                'project/src/Contract.php',
                'project/src/Control.php',
                'project/src/Other.php',
                'project/src/StableConsumer.php',
                'project/src/Target.php',
            ],
        );
    }

    /**
     * Writes the container section with the given definition and singleton entries, after one unrelated definition.
     *
     * @phpstan-param list<string> $definitions
     * @phpstan-param list<string> $singletons
     */
    private function writeContainer(array $definitions, array $singletons): void
    {
        $definitionLines = implode("\n        ", ["'cache.stable' => 'yii\\caching\\DummyCache',", ...$definitions]);
        $singletonLines = implode("\n        ", $singletons);

        $this->writeFile(
            'project/container.php',
            <<<PHP
            <?php

            declare(strict_types=1);

            return [
                'definitions' => [
                    {$definitionLines}
                ],
                'singletons' => [
                    {$singletonLines}
                ],
            ];

            PHP,
        );
    }

    /**
     * Writes `Probe\Target`, optionally with a setter for the `colour` option and with extra members.
     */
    private function writeTarget(bool $withSetter, string $members = ''): void
    {
        $setter = $withSetter ? "\n    public function setColour(string \$colour): void {}\n" : '';
        $setter .= $members === '' ? '' : "\n{$members}";

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
