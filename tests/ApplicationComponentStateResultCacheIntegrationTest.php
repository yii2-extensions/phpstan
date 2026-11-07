<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use yii2\extensions\phpstan\ServiceMapResultCacheValueExtension;
use yii2\extensions\phpstan\tests\provider\ApplicationComponentStateResultCacheIntegrationProvider;
use yii2\extensions\phpstan\tests\support\ResultCacheIntegrationTrait;

use function array_diff;
use function array_splice;
use function array_values;
use function dirname;
use function explode;
use function implode;
use function time;

use const DIRECTORY_SEPARATOR;

/**
 * Integration tests for the application component state transitions that {@see ServiceMapResultCacheValueExtension}
 * exposes to the PHPStan result cache across cold and warm runs.
 *
 * {@see ApplicationComponentStateResultCacheIntegrationProvider} for test case data providers.
 */
final class ApplicationComponentStateResultCacheIntegrationTest extends TestCase
{
    use ResultCacheIntegrationTrait;

    /**
     * Class definition line of the changing component, added when the component was missing and replaced when it
     * becomes unresolved.
     */
    private const string ADDED_DEFINITION = "    'changing' => ['class' => 'yii\\\\caching\\\\FileCache'],";

    /**
     * Expected diagnostics of the control file, which no component change may alter.
     */
    private const array CONTROL = [
        ['src/Control.php', 14, 'phpstan.dumpType', 'string'],
        ['src/Control.php', 15, 'offsetAccess.notFound', null],
        ['src/Control.php', 15, 'phpstan.dumpType', '*ERROR*'],
    ];

    /**
     * Expected diagnostics of the consumers of the changing component while it resolves to `FileCache`.
     */
    private const array FILE_CACHE_CONSUMERS = [
        ['src/ChangingLocatorConsumer.php', 11, 'phpstan.dumpType', 'yii\caching\FileCache'],
        ['src/ChangingLocatorConsumer.php', 12, 'phpstan.dumpType', 'string'],
        ['src/ChangingPropertyConsumer.php', 11, 'phpstan.dumpType', 'yii\caching\FileCache'],
        ['src/ChangingPropertyConsumer.php', 12, 'phpstan.dumpType', 'string'],
    ];

    /**
     * Files each unchanged warm run before the component is added may reanalyse: none, or the two application property
     * readers, whose class dependency on the configured application PHPStan 2.3.4 hashes with a file path that the
     * autoloader registered by Yii 2.0.55's `Yii.php` spells differently; forked workers reuse it after one warm run,
     * while spawned workers, the only kind on Windows, resolve it again on every run.
     */
    private const array INITIAL_WARM_REANALYSED = [0, 2];

    /**
     * Readers of the core component plus the two other application property consumers, which share the application
     * class dependency that PHPStan tracks per class.
     */
    private const int MAXIMUM_CORE_REANALYSED = 4;

    /**
     * Readers of the changing component plus the stable property consumer, which shares the application class
     * dependency that PHPStan tracks per class.
     */
    private const int MAXIMUM_REANALYSED = 3;

    /**
     * Readers of the changing component: its locator and property consumers.
     */
    private const int MINIMUM_REANALYSED = 2;

    /**
     * Expected diagnostics of the consumers of the component whose definition never changes.
     */
    private const array STABLE_CONSUMERS = [
        ['src/StableLocatorConsumer.php', 11, 'phpstan.dumpType', 'yii\caching\DummyCache'],
        ['src/StableLocatorConsumer.php', 12, 'phpstan.dumpType', 'string'],
        ['src/StablePropertyConsumer.php', 11, 'phpstan.dumpType', 'yii\caching\DummyCache'],
        ['src/StablePropertyConsumer.php', 12, 'phpstan.dumpType', 'string'],
    ];

    /**
     * Prefix of the temporary directory owned by each test.
     */
    private const string TEMPORARY_PREFIX = 'yii2-phpstan-component-state-result-cache-';

    /**
     * Definition line that replaces the changing component class with a factory declaring the same class.
     */
    private const string TYPED_FACTORY_DEFINITION = "    'changing' => static fn(): \\yii\\caching\\FileCache => new \\yii\\caching\\FileCache(),";

    /**
     * Definition line that replaces the changing component class with a factory whose class can't be determined.
     */
    private const string UNRESOLVED_DEFINITION = "    'changing' => static fn() => new \\yii\\caching\\FileCache(),";

    public function testKeepComponentReadersWhenTypedFactoryKeepsClass(): void
    {
        $stableInputs = $this->stableInputHashes();

        $results = [
            ...self::FILE_CACHE_CONSUMERS,
            ...self::CONTROL,
            ...self::STABLE_CONSUMERS,
        ];

        $this->writeComponents(self::ADDED_DEFINITION);

        $initialLines = explode("\n", $this->readFile('project/components.php'));

        $coldStartedAt = time();

        $initialCold = $this->analyse();

        self::assertStringContainsString(
            'Result cache not used because the cache file does not exist.',
            $initialCold['stderr'],
            'Initial run must start without a result cache.',
        );
        self::assertStringContainsString(
            'Result cache is saved.',
            $initialCold['stderr'],
            'Initial run must write the result cache.',
        );
        self::assertSame(
            $results,
            $initialCold['results'],
            'Initial run must resolve the configured class on both routes.',
        );

        $this->assertFirstWarmRunUnchanged(
            $this->analyse(),
            $results,
            $coldStartedAt,
            'Unchanged initial warm run',
        );

        $this->writeComponents(self::TYPED_FACTORY_DEFINITION);

        $changedLines = explode("\n", $this->readFile('project/components.php'));

        self::assertSame(
            [self::ADDED_DEFINITION],
            array_values(array_diff($initialLines, $changedLines)),
            'Only the class definition line may be replaced.',
        );
        self::assertSame(
            [self::TYPED_FACTORY_DEFINITION],
            array_values(array_diff($changedLines, $initialLines)),
            'Only the typed factory line may be added.',
        );

        $changed = $this->analyse();

        self::assertRestored(
            0,
            $changed['stderr'],
            'Changed warm run',
        );
        self::assertStringContainsString(
            'Result cache was not rewritten because it is unchanged.',
            $changed['stderr'],
            'An unchanged component class must keep the result cache as it is.',
        );
        self::assertSame(
            $results,
            $changed['results'],
            'Readers must keep the class the typed factory declares.',
        );

        $this->removeTemporaryCacheDirectory();

        $finalCold = $this->analyse();

        self::assertStringContainsString(
            'Result cache not used because the cache file does not exist.',
            $finalCold['stderr'],
            'Final run must start without a result cache.',
        );
        self::assertSame(
            $results,
            $finalCold['results'],
            'Final cold run must infer the typed factory class on both routes.',
        );
        self::assertSame(
            $stableInputs,
            $this->stableInputHashes(),
            'Only the components file may change during the sequence.',
        );
    }

    /**
     * @phpstan-param list<string> $changed Definition lines of the changing component after the change.
     * @phpstan-param list<array{string, int, string, string|null}> $before Reader diagnostics before the change.
     * @phpstan-param list<array{string, int, string, string|null}> $after Reader diagnostics after the change.
     * @phpstan-param list<int> $warmReanalysed Files the unchanged warm run after the change may reanalyse.
     */
    #[DataProviderExternal(ApplicationComponentStateResultCacheIntegrationProvider::class, 'definitionChangeProvider')]
    public function testReanalyseComponentReadersWhenDefinitionChangesState(
        string $initial,
        array $changed,
        array $before,
        array $after,
        array $warmReanalysed,
    ): void {
        $stableInputs = $this->stableInputHashes();
        $before = [...$before, ...self::CONTROL, ...self::STABLE_CONSUMERS];
        $after = [...$after, ...self::CONTROL, ...self::STABLE_CONSUMERS];

        $this->writeComponents($initial);

        $initialLines = explode("\n", $this->readFile('project/components.php'));

        $coldStartedAt = time();

        $initialCold = $this->analyse();

        self::assertStringContainsString(
            'Result cache not used because the cache file does not exist.',
            $initialCold['stderr'],
            'Initial run must start without a result cache.',
        );
        self::assertStringContainsString(
            'Result cache is saved.',
            $initialCold['stderr'],
            'Initial run must write the result cache.',
        );
        self::assertSame(
            $before,
            $initialCold['results'],
            'Initial run must infer the initial definition state on both routes.',
        );

        $this->assertFirstWarmRunUnchanged(
            $this->analyse(),
            $before,
            $coldStartedAt,
            'Unchanged initial warm run',
        );

        $this->writeComponents(...$changed);

        $changedLines = explode("\n", $this->readFile('project/components.php'));

        self::assertSame(
            [$initial],
            array_values(array_diff($initialLines, $changedLines)),
            'Only the initial changing component line may be replaced or removed.',
        );
        self::assertSame(
            $changed,
            array_values(array_diff($changedLines, $initialLines)),
            'Only the changed component line may be added.',
        );

        $changedRun = $this->analyse();
        $reanalysed = self::reanalysedFiles($changedRun['stderr'], 'Changed warm run');

        self::assertGreaterThanOrEqual(
            self::MINIMUM_REANALYSED,
            $reanalysed,
            'Both readers of the changed component must be reanalysed.',
        );
        self::assertLessThanOrEqual(
            self::MAXIMUM_REANALYSED,
            $reanalysed,
            'Only files depending on the changed component or on the application class may be reanalysed.',
        );
        self::assertStringContainsString(
            'Result cache is saved.',
            $changedRun['stderr'],
            'Changed warm run must save the new results.',
        );
        self::assertSame(
            $after,
            $changedRun['results'],
            'Readers must see the changed state while stable readers and the control keep their results.',
        );

        $changedWarm = $this->analyse();
        $changedWarmReanalysed = self::reanalysedFiles($changedWarm['stderr'], 'Unchanged changed warm run');

        self::assertContains(
            $changedWarmReanalysed,
            $warmReanalysed,
            'Unchanged changed warm run may only reanalyse the accepted application property readers.',
        );

        if ($changedWarmReanalysed === 0) {
            self::assertStringContainsString(
                'Result cache was not rewritten because it is unchanged.',
                $changedWarm['stderr'],
                'Unchanged changed warm run must keep the result cache as it is.',
            );
        }

        self::assertSame(
            $after,
            $changedWarm['results'],
            'Unchanged changed warm run must restore the changed results.',
        );

        $this->removeTemporaryCacheDirectory();

        $finalCold = $this->analyse();

        self::assertStringContainsString(
            'Result cache not used because the cache file does not exist.',
            $finalCold['stderr'],
            'Final run must start without a result cache.',
        );
        self::assertSame(
            $after,
            $finalCold['results'],
            'Final cold results must match the results after detecting the change.',
        );
        self::assertSame(
            $stableInputs,
            $this->stableInputHashes(),
            'Only the components file may change during the sequence.',
        );
    }

    public function testReanalyseComponentReadersWhenMissingComponentIsDefinedWithClass(): void
    {
        $stableInputs = $this->stableInputHashes();

        $before = [
            ['src/ChangingLocatorConsumer.php', 11, 'phpstan.dumpType', 'object'],
            ['src/ChangingLocatorConsumer.php', 12, 'phpstan.dumpType', '*ERROR*'],
            ['src/ChangingLocatorConsumer.php', 12, 'property.notFound', null],
            ['src/ChangingPropertyConsumer.php', 11, 'phpstan.dumpType', '*ERROR*'],
            ['src/ChangingPropertyConsumer.php', 11, 'property.notFound', null],
            ['src/ChangingPropertyConsumer.php', 12, 'phpstan.dumpType', 'mixed'],
            ['src/ChangingPropertyConsumer.php', 12, 'property.nonObject', null],
            ['src/ChangingPropertyConsumer.php', 12, 'property.notFound', null],
            ...self::CONTROL,
            ...self::STABLE_CONSUMERS,
        ];
        $after = [
            ['src/ChangingLocatorConsumer.php', 11, 'phpstan.dumpType', 'yii\caching\FileCache'],
            ['src/ChangingLocatorConsumer.php', 12, 'phpstan.dumpType', 'string'],
            ['src/ChangingPropertyConsumer.php', 11, 'phpstan.dumpType', 'yii\caching\FileCache'],
            ['src/ChangingPropertyConsumer.php', 12, 'phpstan.dumpType', 'string'],
            ...self::CONTROL,
            ...self::STABLE_CONSUMERS,
        ];

        $this->writeComponents();

        $initialComponents = $this->readFile('project/components.php');
        $initialHash = self::hashFile($this->path('project/components.php'));
        $initialCold = $this->analyse();

        self::assertStringContainsString(
            'Result cache not used because the cache file does not exist.',
            $initialCold['stderr'],
            'Initial run must start without a result cache.',
        );
        self::assertStringContainsString(
            'Result cache is saved.',
            $initialCold['stderr'],
            'Initial run must write the result cache.',
        );
        self::assertFileExists(
            $this->path('tmp/resultCache.php'),
            'Result cache file must be created in the owned `tmpDir`.',
        );
        self::assertSame(
            $before,
            $initialCold['results'],
            'Initial run must leave the missing component unresolved on both routes, with its diagnostics.',
        );

        $initialWarm = $this->analyse();

        self::assertContains(
            self::reanalysedFiles($initialWarm['stderr'], 'First unchanged warm run'),
            self::INITIAL_WARM_REANALYSED,
            'First unchanged warm run may only reanalyse the application property readers.',
        );
        self::assertSame(
            $before,
            $initialWarm['results'],
            'First unchanged warm run must keep the initial results.',
        );

        $stableWarm = $this->analyse();
        $stableReanalysed = self::reanalysedFiles($stableWarm['stderr'], 'Second unchanged warm run');

        self::assertContains(
            $stableReanalysed,
            self::INITIAL_WARM_REANALYSED,
            'Second unchanged warm run may only reanalyse the application property readers.',
        );

        if ($stableReanalysed === 0) {
            self::assertStringContainsString(
                'Result cache was not rewritten because it is unchanged.',
                $stableWarm['stderr'],
                'Unchanged initial warm run must keep the result cache as it is.',
            );
        }

        self::assertSame(
            $before,
            $stableWarm['results'],
            'Unchanged initial warm run must restore the initial results.',
        );

        $this->writeComponents(self::ADDED_DEFINITION);

        $changedHash = self::hashFile($this->path('project/components.php'));
        $expectedLines = explode("\n", $initialComponents);

        array_splice($expectedLines, 5, 0, [self::ADDED_DEFINITION]);

        self::assertNotSame(
            $initialHash,
            $changedHash,
            'Components file must change its contents.',
        );
        self::assertSame(
            $expectedLines,
            explode("\n", $this->readFile('project/components.php')),
            'Only the changing component line may be added.',
        );

        $changed = $this->analyse();
        $reanalysed = self::reanalysedFiles($changed['stderr'], 'Changed warm run');

        self::assertGreaterThanOrEqual(
            self::MINIMUM_REANALYSED,
            $reanalysed,
            'Both readers of the added component must be reanalysed.',
        );
        self::assertLessThanOrEqual(
            self::MAXIMUM_REANALYSED,
            $reanalysed,
            'Only files depending on the added component or on the application class may be reanalysed.',
        );
        self::assertStringContainsString(
            'Result cache is saved.',
            $changed['stderr'],
            'Changed warm run must save the new results.',
        );
        self::assertSame(
            $after,
            $changed['results'],
            'Readers of the added component must see its class and lose their stale diagnostics.',
        );
        self::assertNotSame(
            self::resultsOf('src/ChangingLocatorConsumer.php', $before),
            self::resultsOf('src/ChangingLocatorConsumer.php', $changed['results']),
            'Locator consumer results must come from a new analysis.',
        );
        self::assertNotSame(
            self::resultsOf('src/ChangingPropertyConsumer.php', $before),
            self::resultsOf('src/ChangingPropertyConsumer.php', $changed['results']),
            'Property consumer results must come from a new analysis.',
        );
        self::assertSame(
            self::STABLE_CONSUMERS,
            [
                ...self::resultsOf('src/StableLocatorConsumer.php', $changed['results']),
                ...self::resultsOf('src/StablePropertyConsumer.php', $changed['results']),
            ],
            'Stable component consumer results must be unaffected by another component definition.',
        );
        self::assertSame(
            self::CONTROL,
            self::resultsOf('src/Control.php', $changed['results']),
            'Control results must be unaffected by the component definition.',
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

        $this->removeTemporaryCacheDirectory();

        $finalCold = $this->analyse();

        self::assertStringContainsString(
            'Result cache not used because the cache file does not exist.',
            $finalCold['stderr'],
            'Final run must start without a result cache.',
        );
        self::assertSame(
            $changed['results'],
            $finalCold['results'],
            'Final cold results must match the results after detecting the change.',
        );
        self::assertSame(
            $changedHash,
            self::hashFile($this->path('project/components.php')),
            'Final runs must use the changed components.',
        );
        self::assertSame(
            $stableInputs,
            $this->stableInputHashes(),
            'Only the components file may change during the sequence.',
        );
    }

    public function testReanalyseComponentReadersWhenResolvedComponentBecomesUnresolved(): void
    {
        $stableInputs = $this->stableInputHashes();

        $before = [
            ['src/ChangingLocatorConsumer.php', 11, 'phpstan.dumpType', 'yii\caching\FileCache'],
            ['src/ChangingLocatorConsumer.php', 12, 'phpstan.dumpType', 'string'],
            ['src/ChangingPropertyConsumer.php', 11, 'phpstan.dumpType', 'yii\caching\FileCache'],
            ['src/ChangingPropertyConsumer.php', 12, 'phpstan.dumpType', 'string'],
            ...self::CONTROL,
            ...self::STABLE_CONSUMERS,
        ];
        $after = [
            ['src/ChangingLocatorConsumer.php', 11, 'phpstan.dumpType', 'object'],
            ['src/ChangingLocatorConsumer.php', 12, 'phpstan.dumpType', '*ERROR*'],
            ['src/ChangingLocatorConsumer.php', 12, 'property.notFound', null],
            ['src/ChangingPropertyConsumer.php', 11, 'phpstan.dumpType', 'object'],
            ['src/ChangingPropertyConsumer.php', 12, 'phpstan.dumpType', '*ERROR*'],
            ['src/ChangingPropertyConsumer.php', 12, 'property.notFound', null],
            ...self::CONTROL,
            ...self::STABLE_CONSUMERS,
        ];

        $this->writeComponents(self::ADDED_DEFINITION);

        $initialLines = explode("\n", $this->readFile('project/components.php'));
        $initialHash = self::hashFile($this->path('project/components.php'));

        self::assertSame(
            self::ADDED_DEFINITION,
            $initialLines[5] ?? null,
            'Changing component must start with its class definition.',
        );

        $initialCold = $this->analyse();

        self::assertStringContainsString(
            'Result cache not used because the cache file does not exist.',
            $initialCold['stderr'],
            'Initial run must start without a result cache.',
        );
        self::assertStringContainsString(
            'Result cache is saved.',
            $initialCold['stderr'],
            'Initial run must write the result cache.',
        );
        self::assertFileExists(
            $this->path('tmp/resultCache.php'),
            'Result cache file must be created in the owned `tmpDir`.',
        );
        self::assertSame(
            $before,
            $initialCold['results'],
            'Initial run must resolve the configured class on both routes.',
        );

        $initialWarm = $this->analyse();

        self::assertRestored(
            0,
            $initialWarm['stderr'],
            'Unchanged initial warm run',
        );
        // PHPStan doesn't record the stat signature of a file modified in the second it starts reading, so a cold run
        // following `setUp()` within that second leaves the next run to record it by rewriting the cache
        self::assertMatchesRegularExpression(
            '/Result cache (?:was not rewritten because it is unchanged|is saved)\./',
            $initialWarm['stderr'],
            'Unchanged initial warm run must keep or save the result cache.',
        );
        self::assertSame(
            $before,
            $initialWarm['results'],
            'Unchanged initial warm run must restore the initial results.',
        );

        $expectedLines = $initialLines;
        $expectedLines[5] = self::UNRESOLVED_DEFINITION;

        $this->writeFile('project/components.php', implode("\n", $expectedLines));

        $changedHash = self::hashFile($this->path('project/components.php'));

        self::assertNotSame(
            $initialHash,
            $changedHash,
            'Components file must change its contents.',
        );
        self::assertSame(
            $expectedLines,
            explode("\n", $this->readFile('project/components.php')),
            'Only the changing component line may be replaced.',
        );

        $changed = $this->analyse();
        $reanalysed = self::reanalysedFiles($changed['stderr'], 'Changed warm run');

        self::assertGreaterThanOrEqual(
            self::MINIMUM_REANALYSED,
            $reanalysed,
            'Both readers of the changed component must be reanalysed.',
        );
        self::assertLessThanOrEqual(
            self::MAXIMUM_REANALYSED,
            $reanalysed,
            'Only files depending on the changed component or on the application class may be reanalysed.',
        );
        self::assertStringContainsString(
            'Result cache is saved.',
            $changed['stderr'],
            'Changed warm run must save the new results.',
        );
        self::assertSame(
            $after,
            $changed['results'],
            'Readers of the changed component must lose its class and report the unknown property.',
        );
        self::assertNotSame(
            self::resultsOf('src/ChangingLocatorConsumer.php', $before),
            self::resultsOf('src/ChangingLocatorConsumer.php', $changed['results']),
            'Locator consumer results must come from a new analysis.',
        );
        self::assertNotSame(
            self::resultsOf('src/ChangingPropertyConsumer.php', $before),
            self::resultsOf('src/ChangingPropertyConsumer.php', $changed['results']),
            'Property consumer results must come from a new analysis.',
        );
        self::assertSame(
            self::STABLE_CONSUMERS,
            [
                ...self::resultsOf('src/StableLocatorConsumer.php', $changed['results']),
                ...self::resultsOf('src/StablePropertyConsumer.php', $changed['results']),
            ],
            'Stable component consumer results must be unaffected by another component definition.',
        );
        self::assertSame(
            self::CONTROL,
            self::resultsOf('src/Control.php', $changed['results']),
            'Control results must be unaffected by the component definition.',
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

        $this->removeTemporaryCacheDirectory();

        $finalCold = $this->analyse();

        self::assertStringContainsString(
            'Result cache not used because the cache file does not exist.',
            $finalCold['stderr'],
            'Final run must start without a result cache.',
        );
        self::assertSame(
            $changed['results'],
            $finalCold['results'],
            'Final cold results must match the results after detecting the change.',
        );
        self::assertSame(
            $changedHash,
            self::hashFile($this->path('project/components.php')),
            'Final runs must use the changed components.',
        );
        self::assertSame(
            $stableInputs,
            $this->stableInputHashes(),
            'Only the components file may change during the sequence.',
        );
    }

    /**
     * @phpstan-param list<array{string, int, string, string|null}> $before Core reader diagnostics before the change.
     * @phpstan-param list<array{string, int, string, string|null}> $after Core reader diagnostics after the change.
     */
    #[DataProviderExternal(
        ApplicationComponentStateResultCacheIntegrationProvider::class,
        'classlessCoreDefinitionChangeProvider',
    )]
    public function testReanalyseCoreComponentReadersWhenClasslessDefinitionChangesState(
        string $initial,
        string $changed,
        array $before,
        array $after,
    ): void {
        $this->writeConsumer(
            'RequestLocatorConsumer',
            '\yii\di\ServiceLocator $locator',
            "\$locator->get('request')",
            'cookieValidationKey',
        );
        $this->writeConsumer(
            'RequestPropertyConsumer',
            '',
            '\Yii::$app->request',
            'cookieValidationKey',
        );

        $stableInputs = [
            ...$this->stableInputHashes(),
            ...$this->inputHashes(['project/src/RequestLocatorConsumer.php', 'project/src/RequestPropertyConsumer.php']),
        ];

        $before = [
            ...self::FILE_CACHE_CONSUMERS,
            ...self::CONTROL,
            ...$before,
            ...self::STABLE_CONSUMERS,
        ];
        $after = [
            ...self::FILE_CACHE_CONSUMERS,
            ...self::CONTROL,
            ...$after,
            ...self::STABLE_CONSUMERS,
        ];

        $this->writeComponents(self::ADDED_DEFINITION, $initial);

        $initialLines = explode("\n", $this->readFile('project/components.php'));

        $coldStartedAt = time();

        $initialCold = $this->analyse();

        self::assertStringContainsString(
            'Result cache not used because the cache file does not exist.',
            $initialCold['stderr'],
            'Initial run must start without a result cache.',
        );
        self::assertStringContainsString(
            'Result cache is saved.',
            $initialCold['stderr'],
            'Initial run must write the result cache.',
        );
        self::assertSame(
            $before,
            $initialCold['results'],
            'Initial run must infer the initial core definition on both routes.',
        );

        $this->assertFirstWarmRunUnchanged(
            $this->analyse(),
            $before,
            $coldStartedAt,
            'Unchanged initial warm run',
        );

        $this->writeComponents(self::ADDED_DEFINITION, $changed);

        $changedLines = explode("\n", $this->readFile('project/components.php'));

        self::assertSame(
            [$initial],
            array_values(array_diff($initialLines, $changedLines)),
            'Only the initial core component line may be replaced.',
        );
        self::assertSame(
            [$changed],
            array_values(array_diff($changedLines, $initialLines)),
            'Only the changed core component line may be added.',
        );

        $changedRun = $this->analyse();
        $reanalysed = self::reanalysedFiles($changedRun['stderr'], 'Changed warm run');

        self::assertGreaterThanOrEqual(
            self::MINIMUM_REANALYSED,
            $reanalysed,
            'Both readers of the changed core component must be reanalysed.',
        );
        self::assertLessThanOrEqual(
            self::MAXIMUM_CORE_REANALYSED,
            $reanalysed,
            'Only files depending on the core component or on the application class may be reanalysed.',
        );
        self::assertStringContainsString(
            'Result cache is saved.',
            $changedRun['stderr'],
            'Changed warm run must save the new results.',
        );
        self::assertSame(
            $after,
            $changedRun['results'],
            'Locator reader must follow the class key while the other readers keep their results.',
        );
        self::assertSame(
            self::resultsOf('src/RequestPropertyConsumer.php', $before),
            self::resultsOf('src/RequestPropertyConsumer.php', $changedRun['results']),
            'Property route must keep the annotated core type in both states.',
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

        $this->removeTemporaryCacheDirectory();

        $finalCold = $this->analyse();

        self::assertStringContainsString(
            'Result cache not used because the cache file does not exist.',
            $finalCold['stderr'],
            'Final run must start without a result cache.',
        );
        self::assertSame(
            $after,
            $finalCold['results'],
            'Final cold results must match the results after detecting the change.',
        );
        self::assertSame(
            $stableInputs,
            [
                ...$this->stableInputHashes(),
                ...$this->inputHashes(
                    ['project/src/RequestLocatorConsumer.php', 'project/src/RequestPropertyConsumer.php'],
                ),
            ],
            'Only the components file may change during the sequence.',
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
                bootstrapFiles:
                    - {$this->neonPath(self::yiiBootstrapFile())}
                stubFiles:
                    - {$this->neonPath(self::applicationClassesStub())}
                yii2:
                    config_path: {$this->neonPath($this->path('project/config.php'))}

            NEON,
        );
        // params keep project stub validation from reporting the plain `Module::$params` array at level max
        $this->writeFile(
            'project/config.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            return [
                'components' => require __DIR__ . '/components.php',
                'params' => ['mode' => 'alpha'],
                'phpstan' => ['application_type' => 'yii\web\Application'],
            ];

            PHP,
        );
        $this->writeConsumer(
            'ChangingLocatorConsumer',
            '\yii\di\ServiceLocator $locator',
            "\$locator->get('changing')",
            'cachePath',
        );
        $this->writeConsumer(
            'ChangingPropertyConsumer',
            '',
            '\Yii::$app->changing',
            'cachePath',
        );
        $this->writeConsumer(
            'StableLocatorConsumer',
            '\yii\di\ServiceLocator $locator',
            "\$locator->get('stable')",
            'keyPrefix',
        );
        $this->writeConsumer(
            'StablePropertyConsumer',
            '',
            '\Yii::$app->stable',
            'keyPrefix',
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
     * Returns the hashes of every analysis input except the components file.
     *
     * @phpstan-return array<string, string>
     */
    private function stableInputHashes(): array
    {
        return [
            ...$this->inputHashes(
                [
                    'project/phpstan.neon',
                    'project/config.php',
                    'project/src/ChangingLocatorConsumer.php',
                    'project/src/ChangingPropertyConsumer.php',
                    'project/src/Control.php',
                    'project/src/StableLocatorConsumer.php',
                    'project/src/StablePropertyConsumer.php',
                ],
            ),
            'vendor/yiisoft/yii2/Yii.php' => self::hashFile(self::yiiBootstrapFile()),
        ];
    }

    /**
     * Writes the component definitions, the only file that changes, with the given changing component lines.
     */
    private function writeComponents(string ...$changing): void
    {
        $definitions = implode("\n", [...$changing, "    'stable' => ['class' => 'yii\\\\caching\\\\DummyCache'],"]);

        $this->writeFile(
            'project/components.php',
            <<<PHP
            <?php

            declare(strict_types=1);

            return [
            {$definitions}
            ];

            PHP,
        );
    }

    /**
     * Writes a consumer that dumps a component read and one of its properties on lines 11 and 12.
     */
    private function writeConsumer(string $className, string $parameter, string $read, string $property): void
    {
        $this->writeFile(
            "project/src/{$className}.php",
            <<<PHP
            <?php

            declare(strict_types=1);

            namespace Probe;

            final class {$className}
            {
                public function read({$parameter}): void
                {
                    \\PHPStan\\dumpType({$read});
                    \\PHPStan\\dumpType({$read}->{$property});
                }
            }

            PHP,
        );
    }

    /**
     * Returns Yii's `Yii.php`, which Yii 2.0.55 doesn't classmap.
     */
    private static function yiiBootstrapFile(): string
    {
        return dirname(__DIR__)
            . DIRECTORY_SEPARATOR
            . 'vendor'
            . DIRECTORY_SEPARATOR
            . 'yiisoft'
            . DIRECTORY_SEPARATOR
            . 'yii2'
            . DIRECTORY_SEPARATOR
            . 'Yii.php';
    }
}
