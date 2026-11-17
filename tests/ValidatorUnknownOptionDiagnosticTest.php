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
 * Integration tests for the opt-in switch and native diagnostic coexistence of {@see ValidatorUnknownOptionRule}.
 */
final class ValidatorUnknownOptionDiagnosticTest extends TestCase
{
    use ResultCacheIntegrationTrait;

    private const string TEMPORARY_PREFIX = 'yii2-phpstan-validator-unknown-option-diagnostic-';

    #[TestWith([false, false])]
    #[TestWith([true, false])]
    #[TestWith([true, true])]
    public function testEnableOnlyRequestedDiagnostics(bool $enabled, bool $siblings): void
    {
        $rules = $enabled ? "\n            validatorUnknownOption: true" : '';

        if ($siblings) {
            $rules .= "\n            validatorIncompleteRule: true\n            validatorInvalidDefinition: true"
                . "\n            createObjectUnknownOption: true";
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
            foreach ([12, 13] as $line) {
                $expected[] = ['src/Article.php', $line, 'yii2.validator.unknownOption', null];
            }
        }

        if ($siblings) {
            $expected[] = ['src/Article.php', 15, 'yii2.validator.incompleteRule', null];
            $expected[] = ['src/Article.php', 16, 'yii2.validator.invalidDefinition', null];
        }

        sort($expected);

        self::assertSame(
            $expected,
            $this->analyse()['results'],
            'Only enabled diagnostics may accompany the native error.',
        );
    }

    protected function setUp(): void
    {
        $this->createWorkDirectory();
        $this->writeFile('project/config.php', "<?php\nreturn ['params' => ['mode' => 'alpha']];\n");
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
                        ['title', 'string', 'maxx' => 10],
                        ['title', static function (): void {}, 'maxx' => 10],
                        ['title', Missing::class, 'maxx' => 10],
                        ['title'],
                        ['title', ['class' => \yii\validators\StringValidator::class]],
                    ];
                }
            }

            PHP,
        );
    }
}
