<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPUnit\Framework\Attributes\{DataProviderExternal, RequiresPhp};
use PHPUnit\Framework\TestCase;
use yii2\extensions\phpstan\rules\{CreateObjectInvalidOptionTypeRule, CreateObjectUnknownOptionRule};
use yii2\extensions\phpstan\tests\provider\TraitPropertyResultCacheIntegrationProvider;
use yii2\extensions\phpstan\tests\support\ResultCacheIntegrationTrait;

use function array_diff;
use function array_values;
use function dirname;
use function sort;
use function time;

use const DIRECTORY_SEPARATOR;

/**
 * Integration tests for the trait declarations that {@see CreateObjectUnknownOptionRule} and
 * {@see CreateObjectInvalidOptionTypeRule} follow across result cache runs when a trait redeclares an inherited option
 * property.
 *
 * {@see TraitPropertyResultCacheIntegrationProvider} for test case data providers.
 */
#[RequiresPhp('>= 8.5')]
final class TraitPropertyResultCacheIntegrationTest extends TestCase
{
    use ResultCacheIntegrationTrait;

    /**
     * Expected diagnostics of `Probe\Consumer`, by option.
     */
    private const array CONSUMER = [
        'count' => ['src/Consumer.php', 11, 'yii2.createObject.invalidOptionType', null],
        'secret' => ['src/Consumer.php', 11, 'yii2.createObject.unknownOption', null],
    ];

    /**
     * Expected diagnostics of `Probe\StableConsumer`, which configures `Probe\Base` and no change may alter.
     */
    private const array STABLE_CONSUMER = [
        ['src/StableConsumer.php', 11, 'yii2.createObject.invalidOptionType', null],
        ['src/StableConsumer.php', 11, 'yii2.createObject.unknownOption', null],
    ];

    /**
     * Prefix of the temporary directory owned by each test.
     */
    private const string TEMPORARY_PREFIX = 'yii2-phpstan-trait-property-result-cache-';

    /**
     * @phpstan-param list<'count'|'secret'> $initialReported
     * @phpstan-param list<'count'|'secret'> $changedReported
     */
    #[DataProviderExternal(TraitPropertyResultCacheIntegrationProvider::class, 'traitProvider')]
    public function testReanalyseConsumerWhenTraitRedeclarationChanges(
        string $trait,
        string $initialProperty,
        string $changedProperty,
        array $initialReported,
        array $changedReported,
    ): void {
        $this->writeTrait($trait, $initialProperty);

        $changed = "project/src/{$trait}.php";

        $stableInputs = $this->inputHashes(self::inputsExcept($changed));

        $coldStartedAt = time();

        $initialCold = $this->analyse();

        self::assertStringContainsString(
            'Result cache not used because the cache file does not exist.',
            $initialCold['stderr'],
            'Initial run must start without a result cache.',
        );
        self::assertSame(
            self::results($initialReported),
            $initialCold['results'],
            'Initial run must report the initial results.',
        );

        $this->assertFirstWarmRunUnchanged(
            $this->analyse(),
            self::results($initialReported),
            $coldStartedAt,
            'Unchanged initial warm run',
        );

        $this->writeTrait($trait, $changedProperty);

        $changeStartedAt = time();

        $changedRun = $this->analyse();

        // the trait, the class that uses it and the consumer that configures that class
        self::assertRestored(
            3,
            $changedRun['stderr'],
            'Changed warm run',
        );
        self::assertSame(
            self::results($changedReported),
            $changedRun['results'],
            'Consumer must follow the trait declaration.',
        );

        $this->assertFirstWarmRunUnchanged(
            $this->analyse(),
            self::results($changedReported),
            $changeStartedAt,
            'Unchanged changed warm run',
        );

        $this->removeTemporaryCacheDirectory();

        $finalCold = $this->analyse();

        self::assertStringContainsString(
            'Result cache not used because the cache file does not exist.',
            $finalCold['stderr'],
            'Final run must start without a result cache.',
        );
        self::assertSame(
            self::results($changedReported),
            $finalCold['results'],
            'Final cold results must match the results after detecting the change.',
        );
        self::assertSame(
            $stableInputs,
            $this->inputHashes(self::inputsExcept($changed)),
            'Only the changed trait file may change during the sequence.',
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
                phpVersion: 80500
                paths:
                    - {$this->neonPath($this->path('project/src'))}
                tmpDir: {$this->neonPath($this->path('tmp'))}
                stubFiles:
                    - {$this->neonPath(self::applicationClassesStub())}
                ignoreErrors:
                    # PHPStan reads the inherited declaration and reports that the trait's set hook doesn't assign it
                    -
                        identifier: propertySetHook.noAssign
                        reportUnmatched: false
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
                'params' => ['mode' => 'alpha'],
            ];

            PHP,
        );
        $this->writeFile(
            'project/src/Base.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Probe;

            class Base extends \yii\base\BaseObject
            {
                public int $count = 0;

                protected string $secret = '';
            }

            PHP,
        );
        $this->writeFile(
            'project/src/Target.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Probe;

            final class Target extends Base
            {
                use CountTrait;
                use SecretTrait;
            }

            PHP,
        );
        $this->writeTrait('CountTrait', TraitPropertyResultCacheIntegrationProvider::PLAIN_COUNT);
        $this->writeTrait('SecretTrait', TraitPropertyResultCacheIntegrationProvider::PROTECTED_SECRET);

        $consumers = ['Consumer' => 'Target', 'StableConsumer' => 'Base'];

        foreach ($consumers as $consumer => $class) {
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
                        \\yii\\BaseYii::createObject(['class' => {$class}::class, 'count' => 'all', 'secret' => 'open']);
                    }
                }

                PHP,
            );
        }
    }

    /**
     * Returns every analysis input except the given one.
     *
     * @phpstan-return list<string>
     */
    private static function inputsExcept(string $changed): array
    {
        $inputs = [
            'project/phpstan.neon',
            'project/config.php',
            'project/src/Base.php',
            'project/src/Consumer.php',
            'project/src/CountTrait.php',
            'project/src/SecretTrait.php',
            'project/src/StableConsumer.php',
            'project/src/Target.php',
        ];

        return array_values(array_diff($inputs, [$changed]));
    }

    /**
     * Returns the expected sorted results for the options that `Probe\Consumer` reports.
     *
     * @phpstan-param list<'count'|'secret'> $reported
     *
     * @phpstan-return list<array{string, int, string, string|null}>
     */
    private static function results(array $reported): array
    {
        $results = self::STABLE_CONSUMER;

        foreach ($reported as $option) {
            $results[] = self::CONSUMER[$option];
        }

        sort($results);

        return $results;
    }

    /**
     * Writes the trait `Probe\<name>` with one property declaration.
     */
    private function writeTrait(string $name, string $property): void
    {
        $this->writeFile(
            "project/src/{$name}.php",
            "<?php\n\ndeclare(strict_types=1);\n\nnamespace Probe;\n\ntrait {$name}\n{\n{$property}}\n",
        );
    }
}
