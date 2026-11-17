<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use yii2\extensions\phpstan\rules\ValidatorUnknownOptionRule;
use yii2\extensions\phpstan\tests\support\ResultCacheIntegrationTrait;

use function dirname;
use function sort;

/**
 * Integration tests for declaration and inline construction dependencies of {@see ValidatorUnknownOptionRule}.
 */
final class ValidatorUnknownOptionResultCacheIntegrationTest extends TestCase
{
    use ResultCacheIntegrationTrait;

    private const string TEMPORARY_PREFIX = 'yii2-phpstan-validator-unknown-option-result-cache-';

    #[TestWith(['property'])]
    #[TestWith(['inline'])]
    public function testWarmResultsMatchColdResultsAfterChangesAndReverts(string $change): void
    {
        $before = self::results([13, 14]);
        $after = self::results($change === 'property' ? [12, 13, 14] : [14]);

        self::assertSame(
            $before,
            $this->analyse()['results'],
            'Initial cold results must match.',
        );
        self::assertSame(
            $before,
            $this->analyse()['results'],
            'Initial warm results must match.',
        );
        self::assertSame(
            $before,
            $this->analyse()['results'],
            'Repeated warm results must match.',
        );

        if ($change === 'property') {
            $this->writeValidator(false);
        } else {
            $this->writeConfig(true);
        }

        $warm = $this->analyse();

        self::assertGreaterThanOrEqual(
            1,
            self::reanalysedFiles($warm['stderr'], 'Changed'),
            'Model must be reanalysed.',
        );
        self::assertSame(
            $after,
            $warm['results'],
            'Changed warm results must follow the dependency.',
        );
        self::assertSame(
            $after,
            $this->analyse()['results'],
            'Repeated changed results must match.',
        );

        $this->removeTemporaryCacheDirectory();

        self::assertSame(
            $after,
            $this->analyse()['results'],
            'Cold results must equal changed warm results.',
        );

        $this->writeValidator(true);
        $this->writeConfig(false);

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
                        validatorUnknownOption: true

            NEON,
        );
        $this->writeConfig(false);
        $this->writeValidator(true);
        $this->writeFile(
            'project/src/CustomInline.php',
            <<<'PHP'
            <?php
            namespace Probe;
            final class CustomInline extends \yii\validators\InlineValidator
            {
                public int $limit = 1;
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
                        ['title', 'Probe\CustomValidator', 'limit' => 1],
                        ['title', static function (): void {}, 'limit' => 1],
                        ['title', 'string', 'maxx' => 10],
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
            $results[] = ['src/Article.php', $line, 'yii2.validator.unknownOption', null];
        }

        sort($results);

        return $results;
    }

    private function writeConfig(bool $mapInline): void
    {
        $mapping = $mapInline
            ? "'container' => ['definitions' => ['yii\\\\validators\\\\InlineValidator' => 'Probe\\\\CustomInline']],"
            : '';

        $this->writeFile('project/config.php', "<?php\nreturn [{$mapping} 'params' => ['mode' => 'alpha']];\n");
    }

    private function writeValidator(bool $property): void
    {
        $member = $property ? 'public int $limit = 1;' : '';

        $this->writeFile(
            'project/src/CustomValidator.php',
            "<?php\nnamespace Probe;\n"
                . "final class CustomValidator extends \\yii\\validators\\StringValidator {{$member}}\n",
        );
    }
}
