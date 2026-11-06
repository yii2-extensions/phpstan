<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPUnit\Framework\Attributes\{DataProviderExternal, RequiresPhp};
use PHPUnit\Framework\TestCase;
use yii2\extensions\phpstan\rules\CreateObjectUnknownOptionRule;
use yii2\extensions\phpstan\tests\provider\TraitPropertyDefinitionResultCacheIntegrationProvider;
use yii2\extensions\phpstan\tests\support\ResultCacheIntegrationTrait;

use function dirname;
use function sort;
use function time;

use const DIRECTORY_SEPARATOR;

/**
 * Integration tests for the container definition options that {@see CreateObjectUnknownOptionRule} follows across
 * result cache runs when a trait redeclares the inherited property that a definition option sets.
 *
 * {@see TraitPropertyDefinitionResultCacheIntegrationProvider} for test case data providers.
 */
#[RequiresPhp('>= 8.5')]
final class TraitPropertyDefinitionResultCacheIntegrationTest extends TestCase
{
    use ResultCacheIntegrationTrait;

    /**
     * Expected diagnostic of `Probe\Consumer`, which configures `Probe\Target`.
     */
    private const array CONSUMER = ['src/Consumer.php', 11, 'yii2.createObject.unknownOption', null];
    /**
     * Expected diagnostic of `Probe\StableConsumer`, which configures `Probe\Plain` and no change may alter.
     */
    private const array STABLE_CONSUMER = ['src/StableConsumer.php', 11, 'yii2.createObject.unknownOption', null];
    /**
     * Prefix of the temporary directory owned by each test.
     */
    private const string TEMPORARY_PREFIX = 'yii2-phpstan-trait-property-definition-result-cache-';
    /**
     * Trait file that each sequence changes.
     */
    private const string TRAIT_FILE = 'project/src/TriggerTrait.php';

    #[DataProviderExternal(TraitPropertyDefinitionResultCacheIntegrationProvider::class, 'traitProvider')]
    public function testReanalyseConsumerWhenTraitChangesDefinitionOptionProperty(
        string $initialProperty,
        string $changedProperty,
        bool $initialReported,
        bool $changedReported,
    ): void {
        $this->writeTrait($initialProperty);

        $stableInputs = $this->inputHashes(self::stableInputs());

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

        $this->writeTrait($changedProperty);

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
            $this->inputHashes(self::stableInputs()),
            'Only the trait file may change during the sequence.',
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
                'container' => [
                    'definitions' => [
                        'Probe\Plain' => ['class' => 'Probe\Plain', 'trigger' => 1],
                        'Probe\Target' => ['class' => 'Probe\Target', 'trigger' => 1],
                    ],
                ],
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

            class Base extends \yii\base\Component
            {
                public mixed $trigger = null;
            }

            PHP,
        );
        $this->writeFile(
            'project/src/Paint.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Probe;

            /**
             * @extends \yii\base\Behavior<\yii\base\Component>
             */
            final class Paint extends \yii\base\Behavior
            {
                public string $colour = '';
            }

            PHP,
        );

        $classes = ['Plain' => '', 'Target' => "    use TriggerTrait;\n"];

        foreach ($classes as $class => $body) {
            $this->writeFile(
                "project/src/{$class}.php",
                "<?php\n\ndeclare(strict_types=1);\n\nnamespace Probe;\n\n"
                    . "final class {$class} extends Base\n{\n{$body}}\n",
            );
        }

        $consumers = ['Consumer' => 'Target', 'StableConsumer' => 'Plain'];

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
                        \\yii\\BaseYii::createObject(['class' => {$class}::class, 'colour' => 'blue']);
                    }
                }

                PHP,
            );
        }
    }

    /**
     * Returns the expected sorted results, with the diagnostic of `Probe\Consumer` when it is reported.
     *
     * @phpstan-return list<array{string, int, string, string|null}>
     */
    private static function results(bool $consumerReported): array
    {
        $results = [self::STABLE_CONSUMER];

        if ($consumerReported) {
            $results[] = self::CONSUMER;
        }

        sort($results);

        return $results;
    }

    /**
     * Returns every analysis input except the trait file.
     *
     * @phpstan-return list<string>
     */
    private static function stableInputs(): array
    {
        return [
            'project/phpstan.neon',
            'project/config.php',
            'project/src/Base.php',
            'project/src/Consumer.php',
            'project/src/Paint.php',
            'project/src/Plain.php',
            'project/src/StableConsumer.php',
            'project/src/Target.php',
        ];
    }

    /**
     * Writes the trait `Probe\TriggerTrait` with one property declaration.
     */
    private function writeTrait(string $property): void
    {
        $this->writeFile(
            self::TRAIT_FILE,
            "<?php\n\ndeclare(strict_types=1);\n\nnamespace Probe;\n\ntrait TriggerTrait\n{\n{$property}}\n",
        );
    }
}
