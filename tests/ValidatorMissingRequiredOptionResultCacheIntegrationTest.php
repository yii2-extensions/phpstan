<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use yii2\extensions\phpstan\rules\ValidatorMissingRequiredOptionRule;
use yii2\extensions\phpstan\tests\support\ResultCacheIntegrationTrait;

use function dirname;
use function sort;

/**
 * Integration tests for the container construction dependencies of {@see ValidatorMissingRequiredOptionRule}.
 */
final class ValidatorMissingRequiredOptionResultCacheIntegrationTest extends TestCase
{
    use ResultCacheIntegrationTrait;

    /**
     * Files the first unchanged warm run may reanalyse: none, or the model, whose dependencies PHPStan may rehash once
     * after a cold run.
     */
    private const array INITIAL_WARM_REANALYSED = [0, 1];
    private const string TEMPORARY_PREFIX = 'yii2-phpstan-validator-missing-required-option-result-cache-';

    #[TestWith(['option'])]
    #[TestWith(['class'])]
    public function testWarmResultsMatchColdResultsAfterChangesAndReverts(string $change): void
    {
        $before = self::results([12, 13, 14]);
        $after = self::results($change === 'option' ? [14] : [12, 14]);

        self::assertSame(
            $before,
            $this->analyse()['results'],
            'Initial cold results must match.',
        );

        $initialWarm = $this->analyse();

        self::assertContains(
            self::reanalysedFiles($initialWarm['stderr'], 'First unchanged warm run'),
            self::INITIAL_WARM_REANALYSED,
            'First unchanged warm run may only reanalyse the model.',
        );
        self::assertSame(
            $before,
            $initialWarm['results'],
            'First unchanged warm run must keep the initial results.',
        );

        $this->assertWarmRunUnchanged(
            $this->analyse(),
            $before,
            'Unchanged initial warm run',
        );

        $this->writeConfig($change);

        $warm = $this->analyse();

        self::assertGreaterThanOrEqual(
            1,
            self::reanalysedFiles($warm['stderr'], 'Changed'),
            'Model must be reanalysed.',
        );
        self::assertSame(
            $after,
            $warm['results'],
            'Changed warm results must follow the definitions.',
        );

        $this->assertWarmRunUnchanged(
            $this->analyse(),
            $after,
            'Unchanged changed warm run',
        );

        $this->removeTemporaryCacheDirectory();

        self::assertSame(
            $after,
            $this->analyse()['results'],
            'Cold results must equal changed warm results.',
        );

        $this->writeConfig(null);

        self::assertSame(
            $before,
            $this->analyse()['results'],
            'Reverted warm results must restore the diagnostics.',
        );

        $this->removeTemporaryCacheDirectory();

        self::assertSame(
            $before,
            $this->analyse()['results'],
            'Reverted cold results must equal warm results.',
        );
    }

    protected function setUp(): void
    {
        $this->createWorkDirectory();
        $this->writeFile(
            'project/phpstan.neon',
            <<<NEON
            includes:
                - {$this->neonPath(dirname(__DIR__) . '/extension.neon')}
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
                        validatorMissingRequiredOption: true

            NEON,
        );
        $this->writeConfig(null);
        $this->writeFile(
            'project/src/CustomRange.php',
            <<<'PHP'
            <?php
            namespace Probe;
            final class CustomRange extends \yii\validators\RangeValidator
            {
                /** @var list<int> */
                public $range = [1];
            }

            PHP,
        );
        $this->writeFile(
            'project/src/Article.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Probe;

            final class Article extends \yii\base\Model
            {
                public function rules(): array
                {
                    return [
                        ['status', 'in'],
                        ['status', 'Probe\Range'],
                        ['code', 'match'],
                    ];
                }
            }

            PHP,
        );
    }

    /**
     * Returns the diagnostics at the given lines.
     *
     * @param list<int> $lines Reported model lines.
     *
     * @return list<array{string, int, string, null}> Expected results.
     */
    private static function results(array $lines): array
    {
        $results = [];

        foreach ($lines as $line) {
            $results[] = ['src/Article.php', $line, 'yii2.validator.missingRequiredOption', null];
        }

        sort($results);

        return $results;
    }

    /**
     * Writes the analysis configuration: `option` makes the container supply the range of every range validator,
     * `class` maps the probe ID to a subclass, and `null` maps it to the range validator without options.
     */
    private function writeConfig(string|null $change): void
    {
        $range = "'yii\\\\validators\\\\RangeValidator'";

        $definitions = match ($change) {
            'class' => "'Probe\\\\Range' => 'Probe\\\\CustomRange'",
            'option' => "'Probe\\\\Range' => ['class' => {$range}], {$range} => ['range' => [1, 2]]",
            default => "'Probe\\\\Range' => ['class' => {$range}]",
        };

        $this->writeFile(
            'project/config.php',
            "<?php\nreturn ['container' => ['definitions' => [{$definitions}]], 'params' => ['mode' => 'alpha']];\n",
        );
    }
}
