<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use yii2\extensions\phpstan\rules\{NewObjectInvalidOptionTypeRule, NewObjectUnknownOptionRule};
use yii2\extensions\phpstan\tests\provider\NewObjectDiagnosticProvider;
use yii2\extensions\phpstan\tests\support\ResultCacheIntegrationTrait;

use function dirname;
use function implode;

use const DIRECTORY_SEPARATOR;

/**
 * Integration tests for {@see NewObjectUnknownOptionRule} and {@see NewObjectInvalidOptionTypeRule} switches beside
 * native diagnostics in a real PHPStan run.
 *
 * {@see NewObjectDiagnosticProvider} for test case data providers.
 */
final class NewObjectDiagnosticTest extends TestCase
{
    use ResultCacheIntegrationTrait;

    /**
     * Prefix of the temporary directory owned by each test.
     */
    private const string TEMPORARY_PREFIX = 'yii2-phpstan-new-object-diagnostic-';

    /**
     * @phpstan-param list<string> $rules
     * @phpstan-param list<array{string, int, string, null}> $expected
     */
    #[DataProviderExternal(NewObjectDiagnosticProvider::class, 'ruleSwitchProvider')]
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

        // the binding of `Probe\Target` applies to `createObject()` only; `new` builds the written class
        $this->writeFile(
            'project/config.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            return [
                'container' => ['definitions' => ['Probe\Target' => 'Probe\Bound']],
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
        $this->writeFile(
            'project/src/Target.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Probe;

            class Target extends \yii\base\BaseObject
            {
                public int $count = 0;

                public string $name = '';
            }

            PHP,
        );

        $statements = implode("\n        ", NewObjectDiagnosticProvider::STATEMENTS);

        $this->writeFile(
            'project/src/Consumer.php',
            <<<PHP
            <?php

            declare(strict_types=1);

            namespace Probe;

            final class Consumer
            {
                public function create(): void
                {
                    {$statements}
                }
            }

            PHP,
        );
    }
}
