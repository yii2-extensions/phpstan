<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use yii2\extensions\phpstan\rules\{CreateObjectInvalidOptionTypeRule, CreateObjectUnknownOptionRule};
use yii2\extensions\phpstan\tests\provider\CreateObjectComponentOptionResultCacheIntegrationProvider;
use yii2\extensions\phpstan\tests\support\ResultCacheIntegrationTrait;

use function array_diff;
use function array_diff_assoc;
use function dirname;
use function explode;
use function in_array;
use function is_file;
use function str_replace;
use function time;

use const DIRECTORY_SEPARATOR;

/**
 * Integration tests for {@see CreateObjectUnknownOptionRule} and {@see CreateObjectInvalidOptionTypeRule} result cache
 * dependencies of `yii\base\Component` subclasses across cold and warm runs.
 *
 * {@see CreateObjectComponentOptionResultCacheIntegrationProvider} for test case data providers.
 */
final class CreateObjectComponentOptionResultCacheIntegrationTest extends TestCase
{
    use ResultCacheIntegrationTrait;

    /**
     * Expected diagnostics of the control file, which no change may alter.
     */
    private const array CONTROL = [
        ['src/Control.php', 14, 'phpstan.dumpType', 'string'],
        ['src/Control.php', 15, 'offsetAccess.notFound', null],
        ['src/Control.php', 15, 'phpstan.dumpType', '*ERROR*'],
    ];

    /**
     * Project files that the changes may rewrite.
     */
    private const array DECLARATIONS = ['behaviors.php', 'src/Base.php', 'src/Target.php'];

    /**
     * Expected diagnostics of the consumer of `Probe\Other`, which no change may alter.
     */
    private const array STABLE_CONSUMER = [
        ['src/StableConsumer.php', 11, 'yii2.createObject.unknownOption', null],
    ];

    /**
     * Prefix of the temporary directory owned by each test.
     */
    private const string TEMPORARY_PREFIX = 'yii2-phpstan-create-object-component-result-cache-';

    /**
     * @phpstan-param array{string, bool, bool, string} $initial Behavior owner in the map, whether `Probe\Base` and
     * `Probe\Target` override `behaviors()`, and the native type of `Probe\Target::$count`.
     * @phpstan-param array{string, bool, bool, string} $changed
     */
    #[DataProviderExternal(CreateObjectComponentOptionResultCacheIntegrationProvider::class, 'changeProvider')]
    public function testReanalyseConsumersWhenBehaviorsOrNativeTypesOfComponentChange(
        string $file,
        array $initial,
        array $changed,
        int $reanalysed,
    ): void {
        $this->writeDeclarations($initial);

        $stable = [...array_diff(self::DECLARATIONS, [$file])];

        $stableInputs = $this->stableInputHashes($stable);

        $before = self::results($initial);
        $after = self::results($changed);

        $initialContents = $this->readFile("project/{$file}");
        $initialHash = self::hashFile($this->path("project/{$file}"));

        self::assertNotSame(
            $before,
            $after,
            'Change must alter the expected diagnostics.',
        );

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
            'Initial run must report the initial results.',
        );
        $this->assertFirstWarmRunUnchanged(
            $this->analyse(),
            $before,
            $coldStartedAt,
            'Unchanged initial warm run',
        );

        $this->writeDeclarations($changed);

        self::assertNotSame(
            $initialHash,
            self::hashFile($this->path("project/{$file}")),
            'Changed file must change its contents.',
        );
        self::assertCount(
            1,
            array_diff_assoc(
                explode("\n", $this->readFile("project/{$file}")),
                explode("\n", $initialContents),
            ),
            'Only one line of the changed file may differ.',
        );

        $changeStartedAt = time();

        $changedRun = $this->analyse();

        self::assertRestored(
            $reanalysed,
            $changedRun['stderr'],
            'Changed warm run',
        );
        self::assertStringContainsString(
            'Result cache is saved.',
            $changedRun['stderr'],
            'Changed warm run must save the new results.',
        );
        self::assertSame(
            $after,
            $changedRun['results'],
            'Consumers must follow the changed behaviors or native type.',
        );
        $this->assertFirstWarmRunUnchanged(
            $this->analyse(),
            $after,
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
            $after,
            $finalCold['results'],
            'Final cold results must match the results after detecting the change.',
        );
        self::assertSame(
            $stableInputs,
            $this->stableInputHashes($stable),
            'Only the changed file may change during the sequence.',
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
                'behaviors' => require __DIR__ . '/behaviors.php',
                'params' => ['mode' => 'alpha'],
            ];

            PHP,
        );
        $this->writeFile(
            'project/src/Colour.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Probe;

            /**
             * @extends \yii\base\Behavior<\yii\base\Component>
             */
            final class Colour extends \yii\base\Behavior
            {
                public string $colour = '';
            }

            PHP,
        );
        $this->writeFile(
            'project/src/Other.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Probe;

            final class Other extends \yii\base\Component
            {
                public string $name = '';
            }

            PHP,
        );

        $consumers = [
            'ChangingConsumer' => "'Probe\\Target', 'colour' => 'red'",
            'StableConsumer' => "'Probe\\Other', 'colour' => 'red'",
            'TypeConsumer' => "'Probe\\Target', 'count' => 'abc'",
        ];

        foreach ($consumers as $consumer => $configuration) {
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
                        \\yii\\BaseYii::createObject(['class' => {$configuration}]);
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
     * Returns the expected results for the behavior owner in the map, the `behaviors()` overrides and the type.
     *
     * @phpstan-param array{string, bool, bool, string} $state
     *
     * @phpstan-return list<array{string, int, string, string|null}>
     */
    private static function results(array $state): array
    {
        [$owner, $baseBehaviors, $targetBehaviors, $type] = $state;

        $withoutBehaviors = in_array($owner, ['Probe\Base', 'Probe\Target'], true) === false
            && $baseBehaviors === false
            && $targetBehaviors === false;

        return [
            ...($withoutBehaviors ? [['src/ChangingConsumer.php', 11, 'yii2.createObject.unknownOption', null]] : []),
            ...self::CONTROL,
            ...self::STABLE_CONSUMER,
            ...($type === 'int' ? [['src/TypeConsumer.php', 11, 'yii2.createObject.invalidOptionType', null]] : []),
        ];
    }

    /**
     * Returns the hashes of every analysis input except the changing file.
     *
     * @phpstan-param list<string> $declarations Unchanged declarations of the test case.
     *
     * @phpstan-return array<string, string>
     */
    private function stableInputHashes(array $declarations): array
    {
        $files = [
            'project/phpstan.neon',
            'project/config.php',
            'project/src/ChangingConsumer.php',
            'project/src/Colour.php',
            'project/src/Control.php',
            'project/src/Other.php',
            'project/src/StableConsumer.php',
            'project/src/TypeConsumer.php',
        ];

        foreach ($declarations as $declaration) {
            $files[] = "project/{$declaration}";
        }

        self::assertCount(
            2,
            $declarations,
            'Exactly one declaration may change.',
        );

        return $this->inputHashes($files);
    }

    /**
     * Writes a project file only when its contents differ, so that unchanged declarations keep their file signature.
     */
    private function writeChangedFile(string $name, string $contents): void
    {
        if (is_file($this->path($name)) === false || $this->readFile($name) !== $contents) {
            $this->writeFile($name, $contents);
        }
    }

    /**
     * Writes the behavior map, `Probe\Base` and `Probe\Target` for the given state.
     *
     * @phpstan-param array{string, bool, bool, string} $state
     */
    private function writeDeclarations(array $state): void
    {
        [$owner, $baseBehaviors, $targetBehaviors, $type] = $state;

        $owner = str_replace('\\', '\\\\', $owner);
        $override = "    /** @return array{} */ public function behaviors(): array { return []; }\n";
        $inherited = "    // inherits behaviors()\n";

        $this->writeChangedFile(
            'project/behaviors.php',
            <<<PHP
            <?php

            declare(strict_types=1);

            return [
                '{$owner}' => ['Probe\\\\Colour'],
            ];

            PHP,
        );
        $this->writeChangedFile(
            'project/src/Base.php',
            "<?php\n\ndeclare(strict_types=1);\n\nnamespace Probe;\n\nclass Base extends \\yii\\base\\Component\n{\n"
                . ($baseBehaviors ? $override : $inherited) . "}\n",
        );
        $this->writeChangedFile(
            'project/src/Target.php',
            "<?php\n\ndeclare(strict_types=1);\n\nnamespace Probe;\n\nfinal class Target extends Base\n{\n"
                . "    public {$type} \$count;\n" . ($targetBehaviors ? $override : $inherited) . "}\n",
        );
    }
}
