<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support;

use PHPStan\Analyser\Error;
use ReflectionClass;

use function dirname;
use function implode;

/**
 * Provides the diagnostic assertions of the external action rule tests, analysing each fixture once per test class.
 */
trait ExternalActionRuleTestTrait
{
    /**
     * Diagnostics of each analysed fixture file, by path.
     *
     * @var array<string, list<Error>>
     */
    private static array $fixtureErrors = [];

    /**
     * Asserts the line, identifier and message of every diagnostic of a fixture controller file, in report order.
     *
     * @param list<array{string|null, string|null, string}> $expected Action ID and key each diagnostic is reported at,
     * as {@see ActionFixture::line()} locates them, and its message.
     *
     * @phpstan-param class-string $controller
     */
    private function assertControllerDiagnostics(string $controller, array $expected): void
    {
        $actual = [];

        foreach ($this->controllerErrors($controller) as $error) {
            $actual[] = [$error->getLine(), $error->getIdentifier(), $error->getMessage()];
        }

        $expectedDiagnostics = [];

        foreach ($expected as [$actionId, $key, $message]) {
            $expectedDiagnostics[] = [ActionFixture::line($controller, $actionId, $key), self::IDENTIFIER, $message];
        }

        self::assertSame(
            $expectedDiagnostics,
            $actual,
            'Lines, identifiers and messages must match.',
        );
    }

    /**
     * Returns the diagnostics of the files that declare the fixture classes, analysed together on first use, such as a
     * controller and the trait it uses.
     *
     * @phpstan-param class-string ...$classes
     *
     * @return list<Error>
     */
    private function controllerErrors(string ...$classes): array
    {
        $files = [];

        foreach ($classes as $class) {
            $files[] = (string) (new ReflectionClass($class))->getFileName();
        }

        return $this->fileErrors(...$files);
    }

    /**
     * Returns the diagnostics of fixture files analysed together, such as a class and the trait it uses, on first use.
     *
     * @return list<Error>
     */
    private function fileErrors(string ...$files): array
    {
        return self::$fixtureErrors[implode(',', $files)] ??= $this->gatherAnalyserErrors($files);
    }

    /**
     * Returns the path of a fixture file under `tests/data/rule/action`.
     */
    private static function fixtureFile(string $file): string
    {
        return dirname(__DIR__) . '/data/rule/action/' . $file;
    }
}
