<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use yii2\extensions\phpstan\rules\{NewObjectInvalidOptionTypeRule, NewObjectUnknownOptionRule};
use yii2\extensions\phpstan\tests\provider\NewObjectResultCacheIntegrationProvider;
use yii2\extensions\phpstan\tests\support\ResultCacheIntegrationTrait;

use function array_diff;
use function array_values;
use function dirname;
use function implode;
use function sort;

use const DIRECTORY_SEPARATOR;

/**
 * Integration tests for the declaration dependencies of {@see NewObjectUnknownOptionRule} and
 * {@see NewObjectInvalidOptionTypeRule} across cold and warm result cache runs, and for the absence of a container
 * dependency.
 *
 * {@see NewObjectResultCacheIntegrationProvider} for test case data providers.
 */
final class NewObjectResultCacheIntegrationTest extends TestCase
{
    use ResultCacheIntegrationTrait;

    /**
     * Expected diagnostics of the control file, which no change may alter.
     */
    private const array CONTROL = [
        ['src/Control.php', 14, 'phpstan.dumpType', 'string'],
    ];

    /**
     * Expected diagnostic of the `createObject()` consumer, which follows the container binding of `Probe\Target`.
     */
    private const array CREATE_OBJECT_CONSUMER = [
        ['src/CreateObjectConsumer.php', 11, 'yii2.createObject.unknownOption', null],
    ];

    /**
     * Expected invalid option type diagnostic of the `new` consumer.
     */
    private const array NEW_INVALID_OPTION_TYPE = [
        ['src/NewConsumer.php', 11, 'yii2.new.invalidOptionType', null],
    ];

    /**
     * Expected unknown option diagnostic of the `new` consumer.
     */
    private const array NEW_UNKNOWN_OPTION = [
        ['src/NewConsumer.php', 11, 'yii2.new.unknownOption', null],
    ];

    /**
     * Prefix of the temporary directory owned by each test.
     */
    private const string TEMPORARY_PREFIX = 'yii2-phpstan-new-object-result-cache-';

    /**
     * @phpstan-param array{bool, bool} $initialReported
     * @phpstan-param array{bool, bool} $changedReported
     */
    #[DataProviderExternal(NewObjectResultCacheIntegrationProvider::class, 'declarationProvider')]
    public function testFollowDeclarationOfConstructedClass(
        string $initialBase,
        string $changedBase,
        string $initialTarget,
        string $changedTarget,
        int $reanalysed,
        array $initialReported,
        array $changedReported,
    ): void {
        $this->writeContainer([]);
        $this->writeClass('Base', '\\yii\\base\\BaseObject', $initialBase);
        $this->writeClass('Target', 'Base', $initialTarget);

        $changed = $initialBase === $changedBase ? 'project/src/Target.php' : 'project/src/Base.php';

        $stableInputs = $this->inputHashes(self::inputsExcept($changed));
        $initialHash = self::hashFile($this->path($changed));

        // without a binding, the `createObject()` consumer configures the same class as the `new` consumer
        $this->runSequence(
            self::results(...$initialReported, createObjectReported: $initialReported[0]),
            self::results(...$changedReported, createObjectReported: $changedReported[0]),
            $reanalysed,
            function () use ($changedBase, $changedTarget, $changed, $initialHash): void {
                $this->writeClass('Base', '\\yii\\base\\BaseObject', $changedBase);
                $this->writeClass('Target', 'Base', $changedTarget);

                self::assertNotSame(
                    $initialHash,
                    self::hashFile($this->path($changed)),
                    'Changed class file must change its contents.',
                );
            },
        );

        self::assertSame(
            $stableInputs,
            $this->inputHashes(self::inputsExcept($changed)),
            'Only the changed class file may change during the sequence.',
        );
    }

    /**
     * @phpstan-param list<string> $initialDefinitions
     * @phpstan-param list<string> $changedDefinitions
     */
    #[DataProviderExternal(NewObjectResultCacheIntegrationProvider::class, 'bindingProvider')]
    public function testIgnoreContainerBindingOfConstructedClass(
        array $initialDefinitions,
        array $changedDefinitions,
        bool $initialReported,
        bool $changedReported,
    ): void {
        $this->writeContainer($initialDefinitions);
        $this->writeClass('Base', '\\yii\\base\\BaseObject', '');
        $this->writeClass('Target', 'Base', "    public int \$count = 0;\n");

        $stableInputs = $this->inputHashes(self::inputsExcept('project/container.php'));
        $initialHash = self::hashFile($this->path('project/container.php'));

        $this->runSequence(
            self::results(true, true, createObjectReported: $initialReported),
            self::results(true, true, createObjectReported: $changedReported),
            1,
            function () use ($changedDefinitions, $initialHash): void {
                $this->writeContainer($changedDefinitions);

                self::assertNotSame(
                    $initialHash,
                    self::hashFile($this->path('project/container.php')),
                    'Container file must change its contents.',
                );
            },
        );

        self::assertSame(
            $stableInputs,
            $this->inputHashes(self::inputsExcept('project/container.php')),
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
                        newInvalidOptionType: true
                        newUnknownOption: true

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
        $this->writeFile(
            'project/src/Bound.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Probe;

            final class Bound extends \yii\base\BaseObject
            {
                public string $colour = '';
            }

            PHP,
        );

        $consumers = [
            'CreateObjectConsumer' => "\\yii\\BaseYii::createObject(['class' => 'Probe\\Target', 'colour' => 'red']);",
            'NewConsumer' => "new Target(['colour' => 'red', 'count' => 'all']);",
        ];

        foreach ($consumers as $consumer => $statement) {
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
                        {$statement}
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
                }
            }

            PHP,
        );
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
            'project/container.php',
            'project/src/Base.php',
            'project/src/Bound.php',
            'project/src/Control.php',
            'project/src/CreateObjectConsumer.php',
            'project/src/NewConsumer.php',
            'project/src/Target.php',
        ];

        return array_values(array_diff($inputs, [$changed]));
    }

    /**
     * Returns the expected sorted results for the diagnostics each consumer reports.
     *
     * @phpstan-return list<array{string, int, string, string|null}>
     */
    private static function results(bool $unknownOption, bool $invalidOptionType, bool $createObjectReported): array
    {
        $results = [
            ...self::CONTROL,
            ...($createObjectReported ? self::CREATE_OBJECT_CONSUMER : []),
            ...($invalidOptionType ? self::NEW_INVALID_OPTION_TYPE : []),
            ...($unknownOption ? self::NEW_UNKNOWN_OPTION : []),
        ];

        sort($results);

        return $results;
    }

    /**
     * Runs the cold run, the first warm run, the change, the changed warm run with the given number of reanalysed
     * files, the unchanged warm run and the final cold run, asserting the results of each.
     *
     * @phpstan-param list<array{string, int, string, string|null}> $before
     * @phpstan-param list<array{string, int, string, string|null}> $after
     * @phpstan-param callable(): void $change
     */
    private function runSequence(array $before, array $after, int $reanalysed, callable $change): void
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
        self::assertSame(
            $before,
            $initialCold['results'],
            'Initial run must report the initial results.',
        );

        $this->assertWarmRunUnchanged(
            $this->analyse(),
            $before,
            'Unchanged initial warm run',
        );

        $change();

        $changed = $this->analyse();

        self::assertRestored(
            $reanalysed,
            $changed['stderr'],
            'Changed warm run',
        );
        self::assertSame(
            $after,
            $changed['results'],
            'Consumers must report only what the changed declarations and bindings prove.',
        );

        $this->assertWarmRunUnchanged(
            $this->analyse(),
            $after,
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
    }

    /**
     * Writes a class of the `Probe` namespace extending the given class, with the given members.
     */
    private function writeClass(string $name, string $parent, string $members): void
    {
        $members = $members === '' ? '' : "\n{$members}";

        $this->writeFile(
            "project/src/{$name}.php",
            <<<PHP
            <?php

            declare(strict_types=1);

            namespace Probe;

            class {$name} extends {$parent}
            {
                public string \$name = '';
            {$members}}

            PHP,
        );
    }

    /**
     * Writes the container section with the given definition entries, after one unrelated definition.
     *
     * @phpstan-param list<string> $definitions
     */
    private function writeContainer(array $definitions): void
    {
        $definitionLines = implode("\n        ", ["'cache.stable' => 'yii\\caching\\DummyCache',", ...$definitions]);

        $this->writeFile(
            'project/container.php',
            <<<PHP
            <?php

            declare(strict_types=1);

            return [
                'definitions' => [
                    {$definitionLines}
                ],
            ];

            PHP,
        );
    }
}
