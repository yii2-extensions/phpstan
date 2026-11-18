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
 * Integration tests for the opt-in switch and diagnostic coexistence of {@see ValidatorInvalidOptionTypeRule}.
 */
final class ValidatorInvalidOptionTypeDiagnosticTest extends TestCase
{
    use ResultCacheIntegrationTrait;

    private const string TEMPORARY_PREFIX = 'yii2-phpstan-validator-invalid-option-type-diagnostic-';

    #[TestWith([false, false])]
    #[TestWith([true, false])]
    #[TestWith([true, true])]
    #[TestWith([false, true])]
    public function testEnableOnlyRequestedDiagnostics(bool $enabled, bool $siblings): void
    {
        $rules = $enabled ? "\n            validatorInvalidOptionType: true" : '';

        if ($siblings) {
            $rules .= "\n            validatorIncompleteRule: true\n            validatorInvalidDefinition: true"
                . "\n            validatorMissingRequiredOption: true\n            validatorUnknownOption: true"
                . "\n            createObjectInvalidOptionType: true";
        }

        $rules = $rules === '' ? '' : "\n        rules:{$rules}";

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
                    config_path: {$this->neonPath($this->path('project/config.php'))}{$rules}

            NEON,
        );
        $expected = [['src/Article.php', 14, 'class.notFound', null]];

        if ($enabled) {
            $expected[] = ['src/Article.php', 12, 'yii2.validator.invalidOptionType', null];
        }

        if ($siblings) {
            $expected[] = ['src/Article.php', 13, 'yii2.validator.unknownOption', null];
            $expected[] = ['src/Article.php', 15, 'yii2.validator.incompleteRule', null];
            $expected[] = ['src/Article.php', 16, 'yii2.validator.invalidDefinition', null];
            $expected[] = ['src/Article.php', 17, 'yii2.validator.missingRequiredOption', null];
            $expected[] = ['src/Article.php', 24, 'yii2.createObject.invalidOptionType', null];
        }

        sort($expected);

        self::assertSame(
            $expected,
            $this->analyse()['results'],
            'Each rule must report once, only through its enabled identifier.',
        );
    }

    protected function setUp(): void
    {
        $this->createWorkDirectory();
        $this->writeFile('project/config.php', "<?php\nreturn ['params' => ['mode' => 'alpha']];\n");
        $this->writeFile(
            'project/src/Limit.php',
            <<<'PHP'
            <?php
            namespace Probe;
            final class Limit extends \yii\validators\Validator
            {
                public int $limit = 10;
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
                        ['status', Limit::class, 'limit' => 'all'],
                        ['status', Limit::class, 'limt' => 'all'],
                        ['status', Missing::class],
                        ['status'],
                        ['status', ['class' => Limit::class]],
                        ['code', 'match', 'pattern' => null],
                        ['status', Limit::class, 'limit' => '5'],
                    ];
                }

                public function limit(): Limit
                {
                    return \Yii::createObject(['class' => Limit::class, 'limit' => 'all']);
                }
            }

            PHP,
        );
    }
}
