<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use yii2\extensions\phpstan\rules\{ExternalActionInvalidOptionTypeRule, ExternalActionUnknownOptionRule};
use yii2\extensions\phpstan\tests\provider\ExternalActionDiagnosticProvider;
use yii2\extensions\phpstan\tests\support\ResultCacheIntegrationTrait;

use function dirname;
use function implode;

use const DIRECTORY_SEPARATOR;

/**
 * Integration tests for {@see ExternalActionUnknownOptionRule} and {@see ExternalActionInvalidOptionTypeRule} switches
 * beside native diagnostics and the other option rules in a real PHPStan run.
 *
 * {@see ExternalActionDiagnosticProvider} for test case data providers.
 */
final class ExternalActionDiagnosticTest extends TestCase
{
    use ResultCacheIntegrationTrait;

    /**
     * Prefix of the temporary directory owned by each test.
     */
    private const string TEMPORARY_PREFIX = 'yii2-phpstan-external-action-diagnostic-';

    /**
     * @phpstan-param list<string> $rules
     * @phpstan-param list<array{string, int, string, null}> $expected
     */
    #[DataProviderExternal(ExternalActionDiagnosticProvider::class, 'ruleSwitchProvider')]
    public function testReportRuleDiagnosticsOnlyWhenEnabledAndOnceBesideNativeOnes(array $rules, array $expected): void
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
            <<<'PHP'
            <?php

            declare(strict_types=1);

            return [
                'params' => ['mode' => 'alpha'],
            ];

            PHP,
        );
        $this->writeFile(
            'project/src/Target.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Probe;

            final class Target extends \yii\base\Action
            {
                public int $count = 0;
            }

            PHP,
        );
        $this->writeFile(
            'project/src/Plain.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Probe;

            final class Plain extends \yii\base\BaseObject
            {
                public int $count = 0;
            }

            PHP,
        );
        $this->writeFile(
            'project/src/Consumer.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Probe;

            final class Consumer
            {
                public function create(): void
                {
                    \yii\BaseYii::createObject(['class' => Plain::class, 'shade' => 'dark']);
                    new Plain(['shade' => 'dark']);
                }
            }

            PHP,
        );

        $entries = implode("\n            ", ExternalActionDiagnosticProvider::ENTRIES);

        $this->writeFile(
            'project/src/ProbeController.php',
            <<<PHP
            <?php

            declare(strict_types=1);

            namespace Probe;

            final class ProbeController extends \\yii\\base\\Controller
            {
                public function actions(): array
                {
                    // one entry per line
                    return [
                        {$entries}
                    ];
                }
            }

            PHP,
        );
    }
}
