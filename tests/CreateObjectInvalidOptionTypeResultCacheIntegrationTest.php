<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use yii2\extensions\phpstan\rules\CreateObjectInvalidOptionTypeRule;
use yii2\extensions\phpstan\tests\provider\CreateObjectInvalidOptionTypeResultCacheIntegrationProvider;
use yii2\extensions\phpstan\tests\support\ResultCacheIntegrationTrait;

use function array_diff;
use function array_diff_assoc;
use function count;
use function dirname;
use function explode;
use function is_file;
use function time;

use const DIRECTORY_SEPARATOR;

/**
 * Integration tests for {@see CreateObjectInvalidOptionTypeRule} result cache dependencies across cold and warm runs.
 *
 * {@see CreateObjectInvalidOptionTypeResultCacheIntegrationProvider} for test case data providers.
 */
final class CreateObjectInvalidOptionTypeResultCacheIntegrationTest extends TestCase
{
    use ResultCacheIntegrationTrait;

    /**
     * Expected diagnostics of the control file, which no change may alter.
     */
    private const array CONTROL = [
        ['src/Control.php', 14, 'phpstan.dumpType', 'string'],
        ['src/Control.php', 15, 'offsetAccess.notFound', null],
        ['src/Control.php', 15, 'phpstan.dumpType', '*ERROR*'],
    ];

    /**
     * Project files that the changes may rewrite.
     */
    private const array DECLARATIONS = ['src/Base.php', 'src/Shape.php', 'src/Target.php'];

    /**
     * Prefix of the temporary directory owned by each test.
     */
    private const string TEMPORARY_PREFIX = 'yii2-phpstan-create-object-option-type-result-cache-';

    /**
     * @phpstan-param array{string, string, string} $initial Property type, setter parameter type and `Shape` kind.
     * @phpstan-param array{string, string, string} $changed
     */
    #[DataProviderExternal(CreateObjectInvalidOptionTypeResultCacheIntegrationProvider::class, 'declarationProvider')]
    public function testReanalyseConsumersWhenOnlyNativeTypeOfAssignedMemberChanges(
        string $file,
        array $initial,
        array $changed,
        int $reanalysed,
    ): void {
        $this->writeDeclarations($initial);

        $stable = [...array_diff(self::DECLARATIONS, [$file])];

        $stableInputs = $this->stableInputHashes($stable);

        $before = self::results($initial);
        $after = self::results($changed);

        $initialContents = $this->readFile("project/{$file}");
        $initialHash = self::hashFile($this->path("project/{$file}"));

        self::assertNotSame(
            $before,
            $after,
            'Change must alter the expected diagnostics.',
        );

        $coldStartedAt = time();

        $initialCold = $this->analyse();

        self::assertStringContainsString(
            'Result cache not used because the cache file does not exist.',
            $initialCold['stderr'],
            'Initial run must start without a result cache.',
        );
        self::assertStringContainsString(
            'Result cache is saved.',
            $initialCold['stderr'],
            'Initial run must write the result cache.',
        );
        self::assertSame(
            $before,
            $initialCold['results'],
            'Initial run must report the initial results.',
        );
        $this->assertFirstWarmRunUnchanged(
            $this->analyse(),
            $before,
            $coldStartedAt,
            'Unchanged initial warm run',
        );

        $this->writeDeclarations($changed);

        self::assertNotSame(
            $initialHash,
            self::hashFile($this->path("project/{$file}")),
            'Changed declaration must change its contents.',
        );
        self::assertCount(
            1,
            array_diff_assoc(
                explode("\n", $this->readFile("project/{$file}")),
                explode("\n", $initialContents),
            ),
            'Only one line of the declaration may differ.',
        );

        $changeStartedAt = time();

        $changedRun = $this->analyse();

        self::assertRestored(
            $reanalysed,
            $changedRun['stderr'],
            'Changed warm run',
        );
        self::assertStringContainsString(
            'Result cache is saved.',
            $changedRun['stderr'],
            'Changed warm run must save the new results.',
        );
        self::assertSame(
            $after,
            $changedRun['results'],
            'Consumers must follow the changed native type.',
        );
        $this->assertFirstWarmRunUnchanged(
            $this->analyse(),
            $after,
            $changeStartedAt,
            'Unchanged changed warm run',
        );

        $this->removeTemporaryCacheDirectory();

        $finalCold = $this->analyse();

        self::assertStringContainsString(
            'Result cache not used because the cache file does not exist.',
            $finalCold['stderr'],
            'Final run must start without a result cache.',
        );
        self::assertSame(
            $after,
            $finalCold['results'],
            'Final cold results must match the results after detecting the change.',
        );
        self::assertSame(
            $stableInputs,
            $this->stableInputHashes($stable),
            'Only the changed declaration may change during the sequence.',
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
                        createObjectInvalidOptionType: true

            NEON,
        );
        // params keep project stub validation from reporting the plain `Module::$params` array at level max
        $this->writeFile(
            'project/config.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            return ['params' => ['mode' => 'alpha']];

            PHP,
        );
        $this->writeFile(
            'project/src/Other.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Probe;

            class Other {}

            PHP,
        );

        $consumers = [
            'PropertyConsumer' => "'title' => 'abc'",
            'SetterConsumer' => "'limit' => 'abc'",
            'ShapeConsumer' => "'shape' => \$this->other()",
        ];

        foreach ($consumers as $consumer => $option) {
            $this->writeFile(
                "project/src/{$consumer}.php",
                <<<PHP
                <?php

                declare(strict_types=1);

                namespace Probe;

                final class {$consumer}
                {
                    public function create(): void
                    {
                        \\yii\\BaseYii::createObject(['class' => 'Probe\\Target', {$option}]);
                    }

                    public function other(): Other
                    {
                        return new Other();
                    }
                }

                PHP,
            );
        }

        $this->writeFile(
            'project/src/Control.php',
            <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace Probe;

            final class Control
            {
                /**
                 * @param array{mode: string} $values
                 */
                public function read(array $values): void
                {
                    \PHPStan\dumpType($values['mode']);
                    \PHPStan\dumpType($values['extra']);
                }
            }

            PHP,
        );
    }

    /**
     * Returns the expected results for the property type, setter parameter type and `Shape` kind.
     *
     * @phpstan-param array{string, string, string} $types
     *
     * @phpstan-return list<array{string, int, string, string|null}>
     */
    private static function results(array $types): array
    {
        [$property, $setter, $shape] = $types;

        return [
            ...self::CONTROL,
            ...($property === 'int' ? [['src/PropertyConsumer.php', 11, 'yii2.createObject.invalidOptionType', null]] : []),
            ...($setter === 'int' ? [['src/SetterConsumer.php', 11, 'yii2.createObject.invalidOptionType', null]] : []),
            ...($shape === 'final class' ? [['src/ShapeConsumer.php', 11, 'yii2.createObject.invalidOptionType', null]] : []),
        ];
    }

    /**
     * Returns the hashes of every analysis input except the changing declaration.
     *
     * @phpstan-param list<string> $declarations Unchanged declarations of the test case.
     *
     * @phpstan-return array<string, string>
     */
    private function stableInputHashes(array $declarations): array
    {
        $files = [
            'project/phpstan.neon',
            'project/config.php',
            'project/src/Control.php',
            'project/src/Other.php',
            'project/src/PropertyConsumer.php',
            'project/src/SetterConsumer.php',
            'project/src/ShapeConsumer.php',
        ];

        foreach ($declarations as $declaration) {
            $files[] = "project/{$declaration}";
        }

        self::assertCount(
            count(self::DECLARATIONS) - 1,
            $declarations,
            'Exactly one declaration may change.',
        );

        return $this->inputHashes($files);
    }

    /**
     * Writes a project file only when its contents differ, so that unchanged declarations keep their file signature.
     */
    private function writeChangedFile(string $name, string $contents): void
    {
        if (is_file($this->path($name)) === false || $this->readFile($name) !== $contents) {
            $this->writeFile($name, $contents);
        }
    }

    /**
     * Writes `Probe\Base` with the setter parameter type, `Probe\Target` with the property type, and `Probe\Shape`.
     *
     * @phpstan-param array{string, string, string} $types
     */
    private function writeDeclarations(array $types): void
    {
        [$property, $setter, $shape] = $types;

        $this->writeChangedFile(
            'project/src/Base.php',
            <<<PHP
            <?php

            declare(strict_types=1);

            namespace Probe;

            class Base extends \\yii\\base\\BaseObject
            {
                public function setLimit({$setter} \$limit): void {}
            }

            PHP,
        );
        $this->writeChangedFile(
            'project/src/Target.php',
            <<<PHP
            <?php

            declare(strict_types=1);

            namespace Probe;

            class Target extends Base
            {
                public {$property} \$title;

                public Shape|null \$shape = null;
            }

            PHP,
        );
        $this->writeChangedFile(
            'project/src/Shape.php',
            <<<PHP
            <?php

            declare(strict_types=1);

            namespace Probe;

            {$shape} Shape {}

            PHP,
        );
    }
}
