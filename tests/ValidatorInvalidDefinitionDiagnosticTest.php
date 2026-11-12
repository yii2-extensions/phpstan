<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use yii2\extensions\phpstan\rules\ValidatorInvalidDefinitionRule;
use yii2\extensions\phpstan\tests\provider\ValidatorInvalidDefinitionDiagnosticProvider;
use yii2\extensions\phpstan\tests\support\ResultCacheIntegrationTrait;

use function dirname;

use const DIRECTORY_SEPARATOR;

/**
 * Integration tests for the switch of {@see ValidatorInvalidDefinitionRule} beside native diagnostics and the
 * incomplete rule check in a real PHPStan run.
 *
 * {@see ValidatorInvalidDefinitionDiagnosticProvider} for test case data providers.
 */
final class ValidatorInvalidDefinitionDiagnosticTest extends TestCase
{
    use ResultCacheIntegrationTrait;

    /**
     * Prefix of the temporary directory owned by each test.
     */
    private const string TEMPORARY_PREFIX = 'yii2-phpstan-validator-invalid-definition-diagnostic-';

    /**
     * @phpstan-param list<string> $rules
     * @phpstan-param list<array{string, int, string, null}> $expected
     */
    #[DataProviderExternal(ValidatorInvalidDefinitionDiagnosticProvider::class, 'ruleSwitchProvider')]
    public function testReportRuleDiagnosticsOnlyWhenEnabledAndNotOnNativeOnes(array $rules, array $expected): void
    {
        $switches = '';

        foreach ($rules as $rule) {
            $switches .= "\n            {$rule}: true";
        }

        $switches = $switches === '' ? '' : "\n        rules:{$switches}";

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
                    config_path: {$this->neonPath($this->path('project/config.php'))}{$switches}

            NEON,
        );

        self::assertSame(
            $expected,
            $this->analyse()['results'],
            'Lines and identifiers must match the observed diagnostics.',
        );
    }

    protected function setUp(): void
    {
        $this->createWorkDirectory();

        $this->writeFile(
            'project/config.php',
            "<?php\n\ndeclare(strict_types=1);\n\nreturn ['params' => ['mode' => 'alpha']];\n",
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
                        ['title', 'required'],
                        ['title', ['class' => \yii\validators\StringValidator::class]],
                        ['title', new \yii\validators\RequiredValidator()],
                        ['title', new Missing()],
                        [null, ['class' => \yii\validators\StringValidator::class]],
                        ['title'],
                        'title',
                    ];
                }
            }

            PHP,
        );
    }
}
