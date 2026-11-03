<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use yii2\extensions\phpstan\rules\{CreateObjectInvalidOptionTypeRule, CreateObjectUnknownOptionRule};
use yii2\extensions\phpstan\tests\provider\CreateObjectConstructorArgumentResultCacheIntegrationProvider;
use yii2\extensions\phpstan\tests\support\ResultCacheIntegrationTrait;

use function array_diff;
use function array_values;
use function dirname;
use function implode;
use function str_contains;
use function time;

use const DIRECTORY_SEPARATOR;

/**
 * Integration tests for the constructor argument dependencies of {@see CreateObjectUnknownOptionRule} and
 * {@see CreateObjectInvalidOptionTypeRule} across cold and warm result cache runs.
 *
 * {@see CreateObjectConstructorArgumentResultCacheIntegrationProvider} for test case data providers.
 */
final class CreateObjectConstructorArgumentResultCacheIntegrationTest extends TestCase
{
    use ResultCacheIntegrationTrait;

    /**
     * Expected diagnostic of the consumer whose `$params` come from the `Probe\Arguments::PARAMS` constant.
     */
    private const array CONSTANT_CONSUMER = [
        ['src/ConstantConsumer.php', 11, 'yii2.createObject.unknownOption', null],
    ];

    /**
     * Expected diagnostics of the control file, which no change may alter.
     */
    private const array CONTROL = [
        ['src/Control.php', 14, 'phpstan.dumpType', 'string'],
    ];

    /**
     * Expected diagnostic of the consumer that passes its options in the configuration of `Probe\Contract`.
     */
    private const array OPTIONS_CONSUMER = [
        ['src/OptionsConsumer.php', 11, 'yii2.createObject.unknownOption', null],
    ];

    /**
     * Expected diagnostic of the consumer that passes its options as the constructor argument of `Probe\Contract`.
     */
    private const array PARAMS_CONSUMER = [
        ['src/ParamsConsumer.php', 11, 'yii2.createObject.unknownOption', null],
    ];

    /**
     * Initial binding of `Probe\Contract`, one positional constructor param.
     */
    private const string PARAMS_ONE = "'Probe\\Contract' => [['class' => 'Probe\\Target'], [['name' => 'd']]],";

    /**
     * Prefix of the temporary directory owned by each test.
     */
    private const string TEMPORARY_PREFIX = 'yii2-phpstan-create-object-arguments-result-cache-';

    #[DataProviderExternal(CreateObjectConstructorArgumentResultCacheIntegrationProvider::class, 'classProvider')]
    public function testFollowTargetDeclarationOfConstructorArguments(
        string $initialMembers,
        string $changedMembers,
        int $reanalysed,
        bool $initialReported,
        bool $changedReported,
    ): void {
        $this->writeContainer([self::PARAMS_ONE]);
        $this->writeTarget($initialMembers);

        $stableInputs = $this->inputHashes(self::inputsExcept('project/src/Target.php'));

        $before = self::results($initialReported, $initialReported, $initialReported);
        $after = self::results($changedReported, $changedReported, $changedReported);

        $this->runSequence(
            $before,
            $after,
            $reanalysed,
            function () use ($changedMembers): void {
                $this->writeTarget($changedMembers);

                $members = $changedMembers === '' ? '}' : "\n{$changedMembers}}";

                self::assertTrue(
                    str_contains($this->readFile('project/src/Target.php'), "public string \$name = '';\n{$members}"),
                    'Target must hold exactly the changed members.',
                );
            },
        );

        self::assertSame(
            $stableInputs,
            $this->inputHashes(self::inputsExcept('project/src/Target.php')),
            'Only the target class may change during the sequence.',
        );
    }

    /**
     * @phpstan-param list<string> $initialDefinitions
     * @phpstan-param list<string> $changedDefinitions
     * @phpstan-param array{bool, bool} $initialReported
     * @phpstan-param array{bool, bool} $changedReported
     */
    #[DataProviderExternal(CreateObjectConstructorArgumentResultCacheIntegrationProvider::class, 'definitionProvider')]
    public function testReanalyseConsumersWhenDefinitionArgumentsChange(
        array $initialDefinitions,
        array $changedDefinitions,
        array $initialReported,
        array $changedReported,
    ): void {
        $this->writeContainer($initialDefinitions);

        $stableInputs = $this->inputHashes(self::inputsExcept('project/container.php'));

        $initialHash = self::hashFile($this->path('project/container.php'));

        $this->runSequence(
            self::results(...$initialReported, constantReported: true),
            self::results(...$changedReported, constantReported: true),
            2,
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

    #[DataProviderExternal(CreateObjectConstructorArgumentResultCacheIntegrationProvider::class, 'constantProvider')]
    public function testReanalyseConsumerWhenParamsConstantChanges(
        string $initialParams,
        string $changedParams,
        bool $initialReported,
        bool $changedReported,
    ): void {
        $this->writeContainer([self::PARAMS_ONE]);
        $this->writeArguments($initialParams);

        $stableInputs = $this->inputHashes(self::inputsExcept('project/src/Arguments.php'));

        $this->runSequence(
            self::results(true, true, $initialReported),
            self::results(true, true, $changedReported),
            2,
            function () use ($changedParams): void {
                $this->writeArguments($changedParams);
            },
        );

        self::assertSame(
            $stableInputs,
            $this->inputHashes(self::inputsExcept('project/src/Arguments.php')),
            'Only the arguments class may change during the sequence.',
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
        $this->writeTarget('');
        $this->writeArguments("[['colour' => 'red']]");
        $this->writeFile(
            'project/src/Contract.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Probe;

            interface Contract {}

            PHP,
        );

        $consumers = [
            'ConstantConsumer' => "'Probe\\Target', Arguments::PARAMS",
            'OptionsConsumer' => "['class' => 'Probe\\Contract', 'colour' => 'red']",
            'ParamsConsumer' => "'Probe\\Contract', [['colour' => 'red']]",
        ];

        foreach ($consumers as $consumer => $arguments) {
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
                        \\yii\\BaseYii::createObject({$arguments});
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
            'project/src/Arguments.php',
            'project/src/ConstantConsumer.php',
            'project/src/Contract.php',
            'project/src/Control.php',
            'project/src/OptionsConsumer.php',
            'project/src/ParamsConsumer.php',
            'project/src/Target.php',
        ];

        return array_values(array_diff($inputs, [$changed]));
    }

    /**
     * Returns the expected results, with the diagnostic of each consumer that the configuration proves.
     *
     * @phpstan-return list<array{string, int, string, string|null}>
     */
    private static function results(bool $optionsReported, bool $paramsReported, bool $constantReported): array
    {
        return [
            ...($constantReported ? self::CONSTANT_CONSUMER : []),
            ...self::CONTROL,
            ...($optionsReported ? self::OPTIONS_CONSUMER : []),
            ...($paramsReported ? self::PARAMS_CONSUMER : []),
        ];
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

        $change();

        $changeStartedAt = time();

        $changed = $this->analyse();

        self::assertRestored(
            $reanalysed,
            $changed['stderr'],
            'Changed warm run',
        );
        self::assertSame(
            $after,
            $changed['results'],
            'Consumers must report only while their constructor arguments prove the assigned configuration.',
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
    }

    /**
     * Writes `Probe\Arguments` with the given constructor arguments as the `PARAMS` constant.
     */
    private function writeArguments(string $params): void
    {
        $this->writeFile(
            'project/src/Arguments.php',
            <<<PHP
            <?php

            declare(strict_types=1);

            namespace Probe;

            final class Arguments
            {
                public const array PARAMS = {$params};
            }

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

    /**
     * Writes `Probe\Target` with the given extra members.
     */
    private function writeTarget(string $members): void
    {
        $members = $members === '' ? '' : "\n{$members}";

        $this->writeFile(
            'project/src/Target.php',
            <<<PHP
            <?php

            declare(strict_types=1);

            namespace Probe;

            class Target extends \\yii\\base\\BaseObject
            {
                public string \$name = '';
            {$members}}

            PHP,
        );
    }
}
