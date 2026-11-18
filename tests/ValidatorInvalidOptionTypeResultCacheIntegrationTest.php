<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use yii2\extensions\phpstan\rules\ValidatorInvalidOptionTypeRule;
use yii2\extensions\phpstan\tests\support\ResultCacheIntegrationTrait;

use function dirname;
use function sort;

/**
 * Integration tests for the container and declaration dependencies of {@see ValidatorInvalidOptionTypeRule}.
 */
final class ValidatorInvalidOptionTypeResultCacheIntegrationTest extends TestCase
{
    use ResultCacheIntegrationTrait;

    /**
     * Files the first unchanged warm run may reanalyse: none, or the model, whose dependencies PHPStan may rehash once
     * after a cold run.
     */
    private const array INITIAL_WARM_REANALYSED = [0, 1];
    private const string TEMPORARY_PREFIX = 'yii2-phpstan-validator-invalid-option-type-result-cache-';

    #[TestWith(['class'])]
    #[TestWith(['declaration'])]
    public function testWarmResultsMatchColdResultsAfterChangesAndReverts(string $change): void
    {
        $before = self::results([12, 13, 15]);
        $after = self::results($change === 'class' ? [13, 15] : [15]);

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

        $this->writeChange($change);

        $warm = $this->analyse();

        self::assertGreaterThanOrEqual(
            1,
            self::reanalysedFiles($warm['stderr'], 'Changed'),
            'Model must be reanalysed.',
        );
        self::assertSame(
            $after,
            $warm['results'],
            'Changed warm results must follow the change.',
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

        $this->writeChange(null);

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
                        validatorInvalidOptionType: true

            NEON,
        );
        $this->writeChange(null);
        $this->writeFile(
            'project/src/TypedLimit.php',
            <<<'PHP'
            <?php
            namespace Probe;
            final class TypedLimit extends BaseLimit {}

            PHP,
        );
        $this->writeFile(
            'project/src/FixedLimit.php',
            <<<'PHP'
            <?php
            namespace Probe;
            final class FixedLimit extends \yii\validators\Validator
            {
                public int $limit = 10;
            }

            PHP,
        );
        $this->writeFile(
            'project/src/LooseLimit.php',
            <<<'PHP'
            <?php
            namespace Probe;
            final class LooseLimit extends \yii\validators\Validator
            {
                /** @var mixed */
                public $limit;
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
                        ['count', 'Probe\Limited', 'limit' => 'all'],
                        ['count', 'Probe\TypedLimit', 'limit' => 'all'],
                        ['count', 'Probe\Limited', 'limit' => '5'],
                        ['count', 'Probe\FixedLimit', 'limit' => 'all'],
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
            $results[] = ['src/Article.php', $line, 'yii2.validator.invalidOptionType', null];
        }

        sort($results);

        return $results;
    }

    /**
     * Writes the analysis configuration and the parent declaration: `class` maps the probe ID to an untyped validator,
     * `declaration` widens the inherited property type, and `null` restores both.
     */
    private function writeChange(string|null $change): void
    {
        $class = $change === 'class' ? 'Probe\\\\LooseLimit' : 'Probe\\\\TypedLimit';
        $type = $change === 'declaration' ? 'int|string' : 'int';

        $this->writeFile(
            'project/config.php',
            "<?php\nreturn ['container' => ['definitions' => ['Probe\\\\Limited' => ['class' => '{$class}']]], "
                . "'params' => ['mode' => 'alpha']];\n",
        );
        $this->writeFile(
            'project/src/BaseLimit.php',
            "<?php\nnamespace Probe;\nclass BaseLimit extends \\yii\\validators\\Validator\n{\n"
                . "    public {$type} \$limit = 10;\n}\n",
        );
    }
}
