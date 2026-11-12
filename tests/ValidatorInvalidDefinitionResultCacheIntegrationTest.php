<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use yii2\extensions\phpstan\rules\ValidatorInvalidDefinitionRule;
use yii2\extensions\phpstan\tests\provider\ValidatorInvalidDefinitionResultCacheIntegrationProvider;
use yii2\extensions\phpstan\tests\support\ResultCacheIntegrationTrait;

use function dirname;

use const DIRECTORY_SEPARATOR;

/**
 * Integration tests for the owner ancestry and validator type dependencies of {@see ValidatorInvalidDefinitionRule}
 * across cold, warm, changed and reverted result cache runs.
 *
 * {@see ValidatorInvalidDefinitionResultCacheIntegrationProvider} for test case data providers.
 */
final class ValidatorInvalidDefinitionResultCacheIntegrationTest extends TestCase
{
    use ResultCacheIntegrationTrait;

    /**
     * Files the first unchanged warm run may reanalyse: none, or both models, whose class dependency PHPStan 2.3.4
     * hashes through the stub path of a built-in ancestor of `yii\base\Model`, which changes once after a cold run.
     */
    private const array INITIAL_WARM_REANALYSED = [0, 2];

    /**
     * Initial declaration of each class that a change rewrites.
     */
    private const array ORIGINAL = [
        'BaseModel' => 'abstract class BaseModel extends \\yii\\base\\Model {}',
        'Types' => "final class Types\n{\n    /**\n     * @return array<string, string>\n     */\n"
            . "    public static function definition(): array\n    {\n"
            . "        return ['class' => \\yii\\validators\\RequiredValidator::class];\n    }\n}",
    ];

    /**
     * Results while the probe model's rule is consumed with an array type: both invalid types are reported.
     */
    private const array REPORTED = [
        ['src/ProbeModel.php', 12, 'yii2.validator.invalidDefinition', null],
        ['src/StableModel.php', 12, 'yii2.validator.invalidDefinition', null],
    ];

    /**
     * Results after the change: only the unrelated model is reported.
     */
    private const array STOOD_ASIDE = [
        ['src/StableModel.php', 12, 'yii2.validator.invalidDefinition', null],
    ];

    /**
     * Prefix of the temporary directory owned by each test.
     */
    private const string TEMPORARY_PREFIX = 'yii2-phpstan-validator-invalid-definition-result-cache-';

    /**
     * @phpstan-param key-of<self::ORIGINAL> $class
     */
    #[DataProviderExternal(ValidatorInvalidDefinitionResultCacheIntegrationProvider::class, 'changeProvider')]
    public function testReanalyseModelWhenHierarchyOrValidatorTypeChangesAndReverts(
        string $class,
        string $changed,
    ): void {
        $initialCold = $this->analyse();

        self::assertStringContainsString(
            'Result cache not used because the cache file does not exist.',
            $initialCold['stderr'],
            'Initial run must start without a result cache.',
        );
        self::assertSame(
            self::REPORTED,
            $initialCold['results'],
            'Initial run must report both models.',
        );

        $this->assertInitialWarmRuns(self::REPORTED);
        $this->writeClass(
            $class,
            $changed,
        );
        $this->assertChangedWarmRuns(self::STOOD_ASIDE, 'Changed');
        $this->removeTemporaryCacheDirectory();

        self::assertSame(
            self::STOOD_ASIDE,
            $this->analyse()['results'],
            'Cold results of the changed code must match its warm results.',
        );

        $this->writeClass(
            $class,
            self::ORIGINAL[$class],
        );
        $this->assertChangedWarmRuns(self::REPORTED, 'Reverted');
        $this->removeTemporaryCacheDirectory();

        self::assertSame(
            self::REPORTED,
            $this->analyse()['results'],
            'Cold results of the reverted code must match its warm results.',
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
                        validatorInvalidDefinition: true

            NEON,
        );
        // params keep project stub validation from reporting the plain `Module::$params` array at level max
        $this->writeFile(
            'project/config.php',
            "<?php\n\ndeclare(strict_types=1);\n\nreturn ['params' => ['mode' => 'alpha']];\n",
        );

        foreach (self::ORIGINAL as $class => $declaration) {
            $this->writeClass($class, $declaration);
        }

        $this->writeClass(
            'MidModel',
            'abstract class MidModel extends BaseModel {}',
        );
        $this->writeModel(
            'ProbeModel',
            'MidModel',
            'Types::definition()',
        );
        $this->writeModel(
            'StableModel',
            '\\yii\\base\\Model',
            "['class' => \\yii\\validators\\RequiredValidator::class]",
        );
    }

    /**
     * Asserts the warm run that follows a change, which reanalyses the model, and the unchanged warm run after it.
     *
     * @phpstan-param list<array{string, int, string, null}> $expected
     */
    private function assertChangedWarmRuns(array $expected, string $run): void
    {
        $changed = $this->analyse();

        self::assertGreaterThanOrEqual(
            1,
            self::reanalysedFiles($changed['stderr'], "{$run} warm run"),
            "{$run} warm run must reanalyse the model.",
        );
        self::assertSame(
            $expected,
            $changed['results'],
            "{$run} warm run must follow the change.",
        );

        $this->assertWarmRunUnchanged(
            $this->analyse(),
            $expected,
            "Unchanged {$run} warm run",
        );
    }

    /**
     * Asserts the first unchanged warm run, which may reanalyse both models, and the strict warm run after it.
     *
     * @phpstan-param list<array{string, int, string, null}> $expected
     */
    private function assertInitialWarmRuns(array $expected): void
    {
        $initialWarm = $this->analyse();

        self::assertContains(
            self::reanalysedFiles($initialWarm['stderr'], 'First unchanged warm run'),
            self::INITIAL_WARM_REANALYSED,
            'First unchanged warm run may only reanalyse both models.',
        );
        self::assertSame(
            $expected,
            $initialWarm['results'],
            'First unchanged warm run must keep the initial results.',
        );

        $this->assertWarmRunUnchanged(
            $this->analyse(),
            $expected,
            'Unchanged initial warm run',
        );
    }

    /**
     * Writes the class `Probe\<name>` with the given declaration.
     */
    private function writeClass(string $name, string $declaration): void
    {
        $this->writeFile(
            "project/src/{$name}.php",
            "<?php\n\ndeclare(strict_types=1);\n\nnamespace Probe;\n\n{$declaration}\n",
        );
    }

    /**
     * Writes a model extending the given class whose `rules()` returns one rule with the validator type at line 12.
     */
    private function writeModel(string $name, string $parent, string $type): void
    {
        $this->writeClass(
            $name,
            "final class {$name} extends {$parent}\n{\n    public function rules(): array\n    {\n        return [\n"
                . "            ['title', {$type}],\n        ];\n    }\n}",
        );
    }
}
