<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use yii2\extensions\phpstan\ServiceMapResultCacheValueExtension;
use yii2\extensions\phpstan\tests\provider\BehaviorResultCacheIntegrationProvider;
use yii2\extensions\phpstan\tests\support\ResultCacheIntegrationTrait;

use function array_diff_assoc;
use function dirname;
use function explode;
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
    public function testReanalyseBehaviorReadersWhenOnlyTheOwnerBehaviorChanges(
        string $methodDirectory,
        string $propertyDirectory,
    ): void {
        $methodConsumer = "src/{$methodDirectory}/MethodConsumer.php";
        $propertyConsumer = "src/{$propertyDirectory}/PropertyConsumer.php";

        $this->writeConsumer($methodConsumer, 'MethodConsumer', '$owner->mark()');
        $this->writeConsumer($propertyConsumer, 'PropertyConsumer', '$owner->label');

        $stableInputs = $this->stableInputHashes($methodConsumer, $propertyConsumer);
        $before = self::expectedResults(
            [
                'src/AttributeConsumer.php',
                $methodConsumer,
                $propertyConsumer,
            ],
            'int',
            'int<1, max>',
            true,
        );
        $after = self::expectedResults(
            [
                'src/AttributeConsumer.php',
                $methodConsumer,
                $propertyConsumer,
            ],
            'string',
            'int<0, max>',
            false,
        );

        $this->writeBehaviors('Probe\FirstBehavior');

        $initialBehaviors = $this->readFile('project/behaviors.php');
        $initialHash = self::hashFile($this->path('project/behaviors.php'));
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
            'Initial run must infer the first behavior on every route and keep the stable owner and control.',
        );

        $initialWarm = $this->analyse();

        self::assertContains(
            self::reanalysedFiles($initialWarm['stderr'], 'First unchanged warm run'),
            self::INITIAL_WARM_REANALYSED,
            'First unchanged warm run may only reanalyse the `getAttribute()` readers.',
        );
        self::assertSame(
            $before,
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
            $before,
            $stableWarm['results'],
            'Unchanged initial warm run must restore the initial results.',
        );

        $this->writeBehaviors('Probe\SecondBehavior');

        $changedHash = self::hashFile($this->path('project/behaviors.php'));

        self::assertNotSame(
            $initialHash,
            $changedHash,
            'Behaviors file must change its contents.',
        );
        self::assertSame(
            [5 => "    'Probe\\\\Owner' => ['Probe\\\\SecondBehavior'],"],
            array_diff_assoc(
                explode("\n", $this->readFile('project/behaviors.php')),
                explode("\n", $initialBehaviors),
            ),
            'Only the changing owner line may differ.',
        );

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
            'Behavior readers must see the replacement behavior and lose their stale diagnostics.',
        );

        foreach (['src/AttributeConsumer.php', $methodConsumer, $propertyConsumer] as $consumer) {
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
            self::hashFile($this->path('project/behaviors.php')),
            'Final runs must use the changed behaviors.',
        );
        self::assertSame(
            $stableInputs,
            $this->stableInputHashes($methodConsumer, $propertyConsumer),
            'Only the behaviors file may change during the sequence.',
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
     * Writes the behavior lists, the only file that changes.
     */
    private function writeBehaviors(string $ownerBehavior): void
    {
        $value = var_export($ownerBehavior, true);

        $this->writeFile(
            'project/behaviors.php',
            <<<PHP
            <?php

            declare(strict_types=1);

            return [
                'Probe\\\\Owner' => [{$value}],
                'Probe\\\\OtherOwner' => ['Probe\\\\StableBehavior'],
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
