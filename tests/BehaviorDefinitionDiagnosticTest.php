<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use yii2\extensions\phpstan\rules\{
    AttachBehaviorInvalidDefinitionRule,
    AttachBehaviorUnknownOptionRule,
    BehaviorsInvalidDefinitionRule,
    BehaviorsUnknownOptionRule,
    CreateObjectBehaviorUnknownOptionRule,
    CreateObjectInvalidAsDefinitionRule,
};
use yii2\extensions\phpstan\tests\provider\BehaviorDefinitionDiagnosticProvider;
use yii2\extensions\phpstan\tests\support\ResultCacheIntegrationTrait;

use function dirname;

use const DIRECTORY_SEPARATOR;

/**
 * Integration tests for the switches of {@see BehaviorsInvalidDefinitionRule},
 * {@see AttachBehaviorInvalidDefinitionRule}, {@see CreateObjectInvalidAsDefinitionRule},
 * {@see BehaviorsUnknownOptionRule}, {@see AttachBehaviorUnknownOptionRule} and
 * {@see CreateObjectBehaviorUnknownOptionRule} beside native diagnostics in a real PHPStan run.
 *
 * {@see BehaviorDefinitionDiagnosticProvider} for test case data providers.
 */
final class BehaviorDefinitionDiagnosticTest extends TestCase
{
    use ResultCacheIntegrationTrait;

    /**
     * Prefix of the temporary directory owned by each test.
     */
    private const string TEMPORARY_PREFIX = 'yii2-phpstan-behavior-definition-diagnostic-';

    /**
     * @phpstan-param list<string> $rules
     * @phpstan-param list<array{string, int, string, null}> $expected
     */
    #[DataProviderExternal(BehaviorDefinitionDiagnosticProvider::class, 'ruleSwitchProvider')]
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
            'project/src/Plain.php',
            "<?php\n\ndeclare(strict_types=1);\n\nnamespace Probe;\n\nfinal class Plain extends \\yii\\base\\BaseObject {}\n",
        );
        $this->writeFile(
            'project/src/Factory.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Probe;

            final class Factory
            {
                public function create(): object
                {
                    return \yii\BaseYii::createObject(['class' => Owner::class, 'as plain' => Plain::class]);
                }
            }

            PHP,
        );
        $this->writeFile(
            'project/src/Labelled.php',
            "<?php\n\ndeclare(strict_types=1);\n\nnamespace Probe;\n\n"
                . "final class Labelled extends \\yii\\base\\Behavior\n{\n    public string \$label = '';\n}\n",
        );
        $this->writeFile(
            'project/src/OptionOwner.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Probe;

            final class OptionOwner extends \yii\base\Component
            {
                public function behaviors(): array
                {
                    return [
                        'labelled' => ['class' => Labelled::class, 'label' => 'a', 'missing' => 1],
                    ];
                }

                public function register(): object
                {
                    $this->attachBehavior('attached', ['class' => Labelled::class, 'missing' => 1]);

                    return \yii\BaseYii::createObject([
                        'class' => \yii\base\Component::class,
                        'as labelled' => ['class' => Labelled::class, 'missing' => 1],
                    ]);
                }
            }

            PHP,
        );
        $this->writeFile(
            'project/src/Owner.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Probe;

            final class Owner extends \yii\base\Component
            {
                public function behaviors(): array
                {
                    return [
                        'plain' => Plain::class,
                        'noClass' => ['label' => 'value'],
                    ];
                }

                public function register(): void
                {
                    $this->attachBehavior('attached', Plain::class);
                    $this->attachBehavior('closure', static fn(): Plain => new Plain());
                }
            }

            PHP,
        );
    }
}
