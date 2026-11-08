<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use yii2\extensions\phpstan\ServiceMapResultCacheValueExtension;
use yii2\extensions\phpstan\tests\provider\BehaviorResultCacheIntegrationProvider;
use yii2\extensions\phpstan\tests\support\ResultCacheIntegrationTrait;

use function array_map;
use function array_splice;
use function count;
use function dirname;
use function explode;
use function implode;
use function mkdir;
use function sort;
use function sprintf;
use function var_export;

use const DIRECTORY_SEPARATOR;

/**
 * Integration tests for the behavior values that {@see ServiceMapResultCacheValueExtension} exposes to the PHPStan
 * result cache across cold and warm runs.
 */
final class BehaviorResultCacheIntegrationTest extends TestCase
{
    use ResultCacheIntegrationTrait;

    /**
     * Expected diagnostics of the control file, which no behavior change may alter.
     */
    private const array CONTROL = [
        ['src/Control.php', 14, 'phpstan.dumpType', 'string'],
        ['src/Control.php', 15, 'offsetAccess.notFound', null],
        ['src/Control.php', 15, 'phpstan.dumpType', '*ERROR*'],
    ];

    /**
     * Files the first unchanged warm run may reanalyse: none, or the two `getAttribute()` readers, whose class
     * dependency on the Active Record model PHPStan 2.3.4 hashes with a stub path that changes once after a cold run.
     */
    private const array INITIAL_WARM_REANALYSED = [0, 2];

    /**
     * Readers of the changing behaviors plus the native owner consumer, which shares the owner class dependency that
     * PHPStan tracks per class.
     */
    private const int MAXIMUM_REANALYSED = 4;

    /**
     * Readers of the changing behaviors: the method, property and `getAttribute()` consumers.
     */
    private const int MINIMUM_REANALYSED = 3;

    /**
     * Expected diagnostics of the consumer that reads the owner's own method.
     */
    private const array NATIVE_CONSUMER = [
        ['src/late/NativeConsumer.php', 11, 'phpstan.dumpType', 'string'],
    ];

    /**
     * Owner entry of a behaviors file that leaves the changing owner out, unlike an entry with an empty list.
     */
    private const array NO_OWNER_ENTRY = [];

    /**
     * Expected diagnostics of the consumer of the owner whose behaviors never change.
     */
    private const array OTHER_OWNER_CONSUMER = [
        ['src/OtherOwnerConsumer.php', 11, 'phpstan.dumpType', 'float'],
        ['src/OtherOwnerConsumer.php', 12, 'phpstan.dumpType', 'float'],
        ['src/OtherOwnerConsumer.php', 13, 'phpstan.dumpType', 'float'],
    ];

    /**
     * Prefix of the temporary directory owned by each test.
     */
    private const string TEMPORARY_PREFIX = 'yii2-phpstan-behavior-result-cache-';

    #[DataProviderExternal(BehaviorResultCacheIntegrationProvider::class, 'readerOrderProvider')]
    public function testKeepBehaviorReaderResultsWhenAnEmptyOwnerListReplacesTheMissingEntry(
        string $methodDirectory,
        string $propertyDirectory,
    ): void {
        $this->assertReadersKeepResultsWhenOnlyTheOwnerEntryFormChanges(
            self::NO_OWNER_ENTRY,
            self::ownerEntry([]),
            $methodDirectory,
            $propertyDirectory,
        );
    }

    #[DataProviderExternal(BehaviorResultCacheIntegrationProvider::class, 'readerOrderProvider')]
    public function testKeepBehaviorReaderResultsWhenTheEmptyOwnerListIsRemoved(
        string $methodDirectory,
        string $propertyDirectory,
    ): void {
        $this->assertReadersKeepResultsWhenOnlyTheOwnerEntryFormChanges(
            self::ownerEntry([]),
            self::NO_OWNER_ENTRY,
            $methodDirectory,
            $propertyDirectory,
        );
    }

    #[DataProviderExternal(BehaviorResultCacheIntegrationProvider::class, 'readerOrderProvider')]
    public function testReanalyseBehaviorReadersWhenABehaviorIsPrependedToTheOwnerList(
        string $methodDirectory,
        string $propertyDirectory,
    ): void {
        $this->assertReadersFollowOwnerBehaviorChange(
            ['Probe\FirstBehavior'],
            ['Probe\SecondBehavior', 'Probe\FirstBehavior'],
            $methodDirectory,
            $propertyDirectory,
        );
    }

    #[DataProviderExternal(BehaviorResultCacheIntegrationProvider::class, 'readerOrderProvider')]
    public function testReanalyseBehaviorReadersWhenAnEmptyOwnerListGainsABehavior(
        string $methodDirectory,
        string $propertyDirectory,
    ): void {
        $this->assertReadersFollowOwnerEntryChange(
            self::ownerEntry([]),
            self::ownerEntry(['Probe\FirstBehavior']),
            self::resultsWithoutBehaviors($methodDirectory, $propertyDirectory),
            self::resultsWithFirstBehavior($methodDirectory, $propertyDirectory),
            $methodDirectory,
            $propertyDirectory,
        );
    }

    #[DataProviderExternal(BehaviorResultCacheIntegrationProvider::class, 'readerOrderProvider')]
    public function testReanalyseBehaviorReadersWhenAnOwnerEntryIsAdded(
        string $methodDirectory,
        string $propertyDirectory,
    ): void {
        $this->assertReadersFollowOwnerEntryChange(
            self::NO_OWNER_ENTRY,
            self::ownerEntry(['Probe\FirstBehavior']),
            self::resultsWithoutBehaviors($methodDirectory, $propertyDirectory),
            self::resultsWithFirstBehavior($methodDirectory, $propertyDirectory),
            $methodDirectory,
            $propertyDirectory,
        );
    }

    #[DataProviderExternal(BehaviorResultCacheIntegrationProvider::class, 'readerOrderProvider')]
    public function testReanalyseBehaviorReadersWhenOnlyTheOwnerBehaviorChanges(
        string $methodDirectory,
        string $propertyDirectory,
    ): void {
        $this->assertReadersFollowOwnerBehaviorChange(
            ['Probe\FirstBehavior'],
            ['Probe\SecondBehavior'],
            $methodDirectory,
            $propertyDirectory,
        );
    }

    #[DataProviderExternal(BehaviorResultCacheIntegrationProvider::class, 'readerOrderProvider')]
    public function testReanalyseBehaviorReadersWhenTheFirstOwnerBehaviorIsRemoved(
        string $methodDirectory,
        string $propertyDirectory,
    ): void {
        $this->assertReadersFollowOwnerBehaviorChange(
            ['Probe\FirstBehavior', 'Probe\SecondBehavior'],
            ['Probe\SecondBehavior'],
            $methodDirectory,
            $propertyDirectory,
        );
    }

    #[DataProviderExternal(BehaviorResultCacheIntegrationProvider::class, 'readerOrderProvider')]
    public function testReanalyseBehaviorReadersWhenTheOwnerBehaviorOrderIsReversed(
        string $methodDirectory,
        string $propertyDirectory,
    ): void {
        $this->assertReadersFollowOwnerBehaviorChange(
            ['Probe\FirstBehavior', 'Probe\SecondBehavior'],
            ['Probe\SecondBehavior', 'Probe\FirstBehavior'],
            $methodDirectory,
            $propertyDirectory,
        );
    }

    #[DataProviderExternal(BehaviorResultCacheIntegrationProvider::class, 'readerOrderProvider')]
    public function testReanalyseBehaviorReadersWhenTheOwnerEntryIsRemoved(
        string $methodDirectory,
        string $propertyDirectory,
    ): void {
        $this->assertReadersFollowOwnerEntryChange(
            self::ownerEntry(['Probe\FirstBehavior']),
            self::NO_OWNER_ENTRY,
            self::resultsWithFirstBehavior($methodDirectory, $propertyDirectory),
            self::resultsWithoutBehaviors($methodDirectory, $propertyDirectory),
            $methodDirectory,
            $propertyDirectory,
        );
    }

    #[DataProviderExternal(BehaviorResultCacheIntegrationProvider::class, 'readerOrderProvider')]
    public function testReanalyseBehaviorReadersWhenTheOwnerListIsEmptied(
        string $methodDirectory,
        string $propertyDirectory,
    ): void {
        $this->assertReadersFollowOwnerEntryChange(
            self::ownerEntry(['Probe\FirstBehavior']),
            self::ownerEntry([]),
            self::resultsWithFirstBehavior($methodDirectory, $propertyDirectory),
            self::resultsWithoutBehaviors($methodDirectory, $propertyDirectory),
            $methodDirectory,
            $propertyDirectory,
        );
    }

    protected function setUp(): void
    {
        $this->createWorkDirectory();

        foreach (['project/lib', 'project/src/early', 'project/src/late'] as $directory) {
            self::assertTrue(
                mkdir($this->path($directory), 0o700, true),
                sprintf('Directory "%s" must be created.', $directory),
            );
        }

        $this->writeFile(
            'project/phpstan.neon',
            <<<NEON
            includes:
                - {$this->neonPath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'extension.neon')}

            parameters:
                level: max
                paths:
                    - {$this->neonPath($this->path('project/src'))}
                scanDirectories:
                    - {$this->neonPath($this->path('project/lib'))}
                tmpDir: {$this->neonPath($this->path('tmp'))}
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
                'behaviors' => require __DIR__ . '/behaviors.php',
                'params' => ['mode' => 'alpha'],
            ];

            PHP,
        );
        $this->writeFile(
            'project/lib/Owner.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Probe;

            final class Owner extends \yii\db\ActiveRecord
            {
                public function code(): string
                {
                    return 'owner';
                }
            }

            PHP,
        );
        $this->writeFile(
            'project/lib/OtherOwner.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Probe;

            final class OtherOwner extends \yii\db\ActiveRecord {}

            PHP,
        );
        $this->writeBehavior('FirstBehavior', 'int', '1');
        $this->writeBehavior('SecondBehavior', 'string', "'second'");
        $this->writeBehavior('StableBehavior', 'float', '1.5');
        $this->writeConsumer('src/AttributeConsumer.php', 'AttributeConsumer', "\$owner->getAttribute('score')");
        $this->writeFile(
            'project/src/late/NativeConsumer.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Probe;

            final class NativeConsumer
            {
                public function read(Owner $owner): void
                {
                    \PHPStan\dumpType($owner->code());
                }
            }

            PHP,
        );
        $this->writeFile(
            'project/src/OtherOwnerConsumer.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Probe;

            final class OtherOwnerConsumer
            {
                public function read(OtherOwner $owner): void
                {
                    \PHPStan\dumpType($owner->mark());
                    \PHPStan\dumpType($owner->label);
                    \PHPStan\dumpType($owner->getAttribute('score'));
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
     * Runs the unchanged warm analysis after the change and the final cold analysis, and asserts that both keep the
     * changed results and that only the behaviors file changed during the sequence.
     *
     * @phpstan-param list<array{string, int, string, string|null}> $changedResults
     * @phpstan-param array<string, string> $stableInputs
     */
    private function assertFinalRuns(
        array $changedResults,
        string $changedHash,
        array $stableInputs,
        string $methodConsumer,
        string $propertyConsumer,
    ): void {
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
            $changedResults,
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
            $changedResults,
            $finalCold['results'],
            'Final cold results must match the results after detecting the change.',
        );
        self::assertSame(
            $changedHash,
            self::hashFile($this->path('project/behaviors.php')),
            'Final runs must use the changed behaviors.',
        );
        self::assertSame(
            $stableInputs,
            $this->stableInputHashes($methodConsumer, $propertyConsumer),
            'Only the behaviors file may change during the sequence.',
        );
    }

    /**
     * Runs the cold and both unchanged warm analyses before the change, and asserts that they keep the given results.
     *
     * @phpstan-param list<array{string, int, string, string|null}> $expected
     */
    private function assertInitialRuns(array $expected): void
    {
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
            $expected,
            $initialCold['results'],
            'Initial run must infer the initial behaviors on every route and keep the stable owner and control.',
        );

        $initialWarm = $this->analyse();

        self::assertContains(
            self::reanalysedFiles($initialWarm['stderr'], 'First unchanged warm run'),
            self::INITIAL_WARM_REANALYSED,
            'First unchanged warm run may only reanalyse the `getAttribute()` readers.',
        );
        self::assertSame(
            $expected,
            $initialWarm['results'],
            'First unchanged warm run must keep the initial results.',
        );

        $stableWarm = $this->analyse();

        self::assertRestored(
            0,
            $stableWarm['stderr'],
            'Unchanged initial warm run',
        );
        self::assertStringContainsString(
            'Result cache was not rewritten because it is unchanged.',
            $stableWarm['stderr'],
            'Unchanged initial warm run must keep the result cache as it is.',
        );
        self::assertSame(
            $expected,
            $stableWarm['results'],
            'Unchanged initial warm run must restore the initial results.',
        );
    }

    /**
     * Runs the full sequence for one change of the changing owner's behavior list, in which the first listed behavior
     * moves from `FirstBehavior` to `SecondBehavior`.
     *
     * @phpstan-param list<string> $initialBehaviors
     * @phpstan-param list<string> $changedBehaviors
     */
    private function assertReadersFollowOwnerBehaviorChange(
        array $initialBehaviors,
        array $changedBehaviors,
        string $methodDirectory,
        string $propertyDirectory,
    ): void {
        $this->assertReadersFollowOwnerEntryChange(
            self::ownerEntry($initialBehaviors),
            self::ownerEntry($changedBehaviors),
            self::resultsWithFirstBehavior($methodDirectory, $propertyDirectory),
            self::resultsWithSecondBehavior($methodDirectory, $propertyDirectory),
            $methodDirectory,
            $propertyDirectory,
        );
    }

    /**
     * Runs the cold, warm, changed and final cold analyses for one change of the changing owner's entry that changes
     * what its readers see.
     *
     * @phpstan-param list<string> $initialEntry
     * @phpstan-param list<string> $changedEntry
     * @phpstan-param list<array{string, int, string, string|null}> $before
     * @phpstan-param list<array{string, int, string, string|null}> $after
     */
    private function assertReadersFollowOwnerEntryChange(
        array $initialEntry,
        array $changedEntry,
        array $before,
        array $after,
        string $methodDirectory,
        string $propertyDirectory,
    ): void {
        $methodConsumer = self::methodConsumer($methodDirectory);
        $propertyConsumer = self::propertyConsumer($propertyDirectory);

        $this->writeConsumer($methodConsumer, 'MethodConsumer', '$owner->mark()');
        $this->writeConsumer($propertyConsumer, 'PropertyConsumer', '$owner->label');

        $stableInputs = $this->stableInputHashes($methodConsumer, $propertyConsumer);

        $this->writeBehaviors($initialEntry);

        $this->assertInitialRuns($before);

        $changedHash = $this->changeOwnerEntry($initialEntry, $changedEntry);

        $changed = $this->analyse();

        $reanalysed = self::reanalysedFiles($changed['stderr'], 'Changed warm run');

        self::assertGreaterThanOrEqual(
            self::MINIMUM_REANALYSED,
            $reanalysed,
            'Every reader of the changing behaviors must be reanalysed.',
        );
        self::assertLessThanOrEqual(
            self::MAXIMUM_REANALYSED,
            $reanalysed,
            'Only files depending on the changing behaviors or on the owner class may be reanalysed.',
        );
        self::assertStringContainsString(
            'Result cache is saved.',
            $changed['stderr'],
            'Changed warm run must save the new results.',
        );
        self::assertSame(
            $after,
            $changed['results'],
            'Behavior readers must see the new behaviors and lose their stale diagnostics.',
        );

        foreach (self::readers($methodConsumer, $propertyConsumer) as $consumer) {
            self::assertNotSame(
                self::resultsOf($consumer, $before),
                self::resultsOf($consumer, $changed['results']),
                sprintf('Results of "%s" must come from a new analysis.', $consumer),
            );
        }

        self::assertSame(
            self::NATIVE_CONSUMER,
            self::resultsOf('src/late/NativeConsumer.php', $changed['results']),
            'Native owner consumer results must be unaffected by the behaviors.',
        );
        self::assertSame(
            self::OTHER_OWNER_CONSUMER,
            self::resultsOf('src/OtherOwnerConsumer.php', $changed['results']),
            'Stable owner results must be unaffected by another owner behavior list.',
        );
        self::assertSame(
            self::CONTROL,
            self::resultsOf('src/Control.php', $changed['results']),
            'Control results must be unaffected by the behaviors.',
        );

        $this->assertFinalRuns($after, $changedHash, $stableInputs, $methodConsumer, $propertyConsumer);
    }

    /**
     * Runs the cold, warm, changed and final cold analyses for a change between the missing owner entry and an empty
     * owner list, which share the `missing` value, and asserts that the change reanalyses no file.
     *
     * @phpstan-param list<string> $initialEntry
     * @phpstan-param list<string> $changedEntry
     */
    private function assertReadersKeepResultsWhenOnlyTheOwnerEntryFormChanges(
        array $initialEntry,
        array $changedEntry,
        string $methodDirectory,
        string $propertyDirectory,
    ): void {
        $methodConsumer = self::methodConsumer($methodDirectory);
        $propertyConsumer = self::propertyConsumer($propertyDirectory);

        $expected = self::resultsWithoutBehaviors($methodDirectory, $propertyDirectory);

        $this->writeConsumer($methodConsumer, 'MethodConsumer', '$owner->mark()');
        $this->writeConsumer($propertyConsumer, 'PropertyConsumer', '$owner->label');

        $stableInputs = $this->stableInputHashes($methodConsumer, $propertyConsumer);

        $this->writeBehaviors($initialEntry);
        $this->assertInitialRuns($expected);

        $changedHash = $this->changeOwnerEntry($initialEntry, $changedEntry);

        $changed = $this->analyse();

        self::assertRestored(
            0,
            $changed['stderr'],
            'Changed warm run',
        );
        self::assertStringContainsString(
            'Result cache was not rewritten because it is unchanged.',
            $changed['stderr'],
            'Changed warm run must keep the result cache as it is.',
        );
        self::assertSame(
            $expected,
            $changed['results'],
            'Behavior readers must keep their results when the owner has no behaviors in either form.',
        );

        $this->assertFinalRuns(
            $expected,
            $changedHash,
            $stableInputs,
            $methodConsumer,
            $propertyConsumer,
        );
    }

    /**
     * Replaces the changing owner's entry, asserts that no other line of the behaviors file changes and returns the
     * hash of the changed file.
     *
     * @phpstan-param list<string> $initialEntry
     * @phpstan-param list<string> $changedEntry
     */
    private function changeOwnerEntry(array $initialEntry, array $changedEntry): string
    {
        $initialHash = self::hashFile($this->path('project/behaviors.php'));
        $initialLines = explode("\n", $this->readFile('project/behaviors.php'));

        $this->writeBehaviors($changedEntry);

        $changedHash = self::hashFile($this->path('project/behaviors.php'));
        $changedLines = explode("\n", $this->readFile('project/behaviors.php'));

        self::assertNotSame(
            $initialHash,
            $changedHash,
            'Behaviors file must change its contents.',
        );
        self::assertSame(
            $initialEntry,
            array_splice($initialLines, 5, count($initialEntry)),
            'Initial owner entry must start on line index 5.',
        );
        self::assertSame(
            $changedEntry,
            array_splice($changedLines, 5, count($changedEntry)),
            'Changed owner entry must start on line index 5.',
        );
        self::assertSame(
            $initialLines,
            $changedLines,
            'Only the changing owner entry may differ.',
        );

        return $changedHash;
    }

    /**
     * Returns the sorted results of every file for the given types of the changing owner's behavior members and of
     * their length.
     *
     * @phpstan-param list<string> $readers
     *
     * @phpstan-return list<array{string, int, string, string|null}>
     */
    private static function expectedResults(
        array $readers,
        string $type,
        string $lengthType,
        bool $rejectedByStrlen,
    ): array {
        $results = [...self::CONTROL, ...self::NATIVE_CONSUMER, ...self::OTHER_OWNER_CONSUMER];

        foreach ($readers as $reader) {
            $results[] = [$reader, 11, 'phpstan.dumpType', $type];
            $results[] = [$reader, 12, 'phpstan.dumpType', $lengthType];

            if ($rejectedByStrlen) {
                $results[] = [$reader, 12, 'argument.type', null];
            }
        }

        sort($results);

        return $results;
    }

    /**
     * Returns the path of the behavior method reader in the given directory.
     */
    private static function methodConsumer(string $directory): string
    {
        return "src/{$directory}/MethodConsumer.php";
    }

    /**
     * Returns the behaviors file entry that attaches the given list to the changing owner.
     *
     * @phpstan-param list<string> $behaviors
     *
     * @phpstan-return list<string>
     */
    private static function ownerEntry(array $behaviors): array
    {
        $values = array_map(static fn(string $behavior): string => var_export($behavior, true), $behaviors);

        return ["    'Probe\\\\Owner' => [" . implode(', ', $values) . '],'];
    }

    /**
     * Returns the path of the behavior property reader in the given directory.
     */
    private static function propertyConsumer(string $directory): string
    {
        return "src/{$directory}/PropertyConsumer.php";
    }

    /**
     * Returns the readers of the changing owner's behaviors: the `getAttribute()`, method and property consumers.
     *
     * @phpstan-return list<string>
     */
    private static function readers(string $methodConsumer, string $propertyConsumer): array
    {
        return ['src/AttributeConsumer.php', $methodConsumer, $propertyConsumer];
    }

    /**
     * Returns the expected results while `FirstBehavior` is the first behavior of the changing owner.
     *
     * @phpstan-return list<array{string, int, string, string|null}>
     */
    private static function resultsWithFirstBehavior(string $methodDirectory, string $propertyDirectory): array
    {
        return self::expectedResults(
            self::readers(self::methodConsumer($methodDirectory), self::propertyConsumer($propertyDirectory)),
            'int',
            'int<1, max>',
            true,
        );
    }

    /**
     * Returns the expected results while the changing owner has no behaviors: the method and property reads are
     * undefined, and `getAttribute()` falls back to `mixed`.
     *
     * @phpstan-return list<array{string, int, string, string|null}>
     */
    private static function resultsWithoutBehaviors(string $methodDirectory, string $propertyDirectory): array
    {
        $methodConsumer = self::methodConsumer($methodDirectory);
        $propertyConsumer = self::propertyConsumer($propertyDirectory);

        $results = [
            ...self::CONTROL,
            ...self::NATIVE_CONSUMER,
            ...self::OTHER_OWNER_CONSUMER,
            ['src/AttributeConsumer.php', 11, 'phpstan.dumpType', 'mixed'],
            ['src/AttributeConsumer.php', 12, 'argument.type', null],
            ['src/AttributeConsumer.php', 12, 'phpstan.dumpType', 'int<0, max>'],
            [$methodConsumer, 11, 'method.notFound', null],
            [$methodConsumer, 11, 'phpstan.dumpType', '*ERROR*'],
            [$methodConsumer, 12, 'argument.type', null],
            [$methodConsumer, 12, 'method.notFound', null],
            [$methodConsumer, 12, 'phpstan.dumpType', 'int<0, max>'],
            [$propertyConsumer, 11, 'phpstan.dumpType', '*ERROR*'],
            [$propertyConsumer, 11, 'property.notFound', null],
            [$propertyConsumer, 12, 'argument.type', null],
            [$propertyConsumer, 12, 'phpstan.dumpType', 'int<0, max>'],
            [$propertyConsumer, 12, 'property.notFound', null],
        ];

        sort($results);

        return $results;
    }

    /**
     * Returns the expected results while `SecondBehavior` is the first behavior of the changing owner.
     *
     * @phpstan-return list<array{string, int, string, string|null}>
     */
    private static function resultsWithSecondBehavior(string $methodDirectory, string $propertyDirectory): array
    {
        return self::expectedResults(
            self::readers(self::methodConsumer($methodDirectory), self::propertyConsumer($propertyDirectory)),
            'string',
            'int<0, max>',
            false,
        );
    }

    /**
     * Returns the hashes of every analysis input except the behaviors file.
     *
     * @phpstan-return array<string, string>
     */
    private function stableInputHashes(string $methodConsumer, string $propertyConsumer): array
    {
        return $this->inputHashes(
            [
                'project/phpstan.neon',
                'project/config.php',
                'project/lib/FirstBehavior.php',
                'project/lib/OtherOwner.php',
                'project/lib/Owner.php',
                'project/lib/SecondBehavior.php',
                'project/lib/StableBehavior.php',
                'project/src/AttributeConsumer.php',
                'project/src/Control.php',
                'project/src/OtherOwnerConsumer.php',
                'project/src/late/NativeConsumer.php',
                "project/{$methodConsumer}",
                "project/{$propertyConsumer}",
            ],
        );
    }

    /**
     * Writes a behavior whose method, property and `@property` tag share one type.
     */
    private function writeBehavior(string $className, string $type, string $value): void
    {
        $this->writeFile(
            "project/lib/{$className}.php",
            <<<PHP
            <?php

            declare(strict_types=1);

            namespace Probe;

            /**
             * @property {$type} \$score
             */
            final class {$className} extends \\yii\\base\\Behavior
            {
                public {$type} \$label = {$value};

                public function mark(): {$type}
                {
                    return {$value};
                }
            }

            PHP,
        );
    }

    /**
     * Writes the behavior lists, the only file that changes: the changing owner's entry from line index 5, then the
     * stable owner's list.
     *
     * @phpstan-param list<string> $ownerEntry
     */
    private function writeBehaviors(array $ownerEntry): void
    {
        $ownerLines = implode('', array_map(static fn(string $line): string => "{$line}\n", $ownerEntry));

        $this->writeFile(
            'project/behaviors.php',
            <<<PHP
            <?php

            declare(strict_types=1);

            return [
            {$ownerLines}    'Probe\\\\OtherOwner' => ['Probe\\\\StableBehavior'],
            ];

            PHP,
        );
    }

    /**
     * Writes a consumer of the changing owner that dumps a read and its length on lines 11 and 12.
     */
    private function writeConsumer(string $file, string $className, string $read): void
    {
        $this->writeFile(
            "project/{$file}",
            <<<PHP
            <?php

            declare(strict_types=1);

            namespace Probe;

            final class {$className}
            {
                public function read(Owner \$owner): void
                {
                    \\PHPStan\\dumpType({$read});
                    \\PHPStan\\dumpType(\\strlen({$read}));
                }
            }

            PHP,
        );
    }
}
