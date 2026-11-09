<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support;

use PHPStan\Analyser\Error;
use ReflectionClass;

use function implode;

/**
 * Provides the diagnostic assertions of the behavior definition rule tests, analysing each fixture once per test class.
 *
 * The using class declares the reported identifier as `IDENTIFIER`.
 */
trait BehaviorRuleTestTrait
{
    /**
     * Diagnostics of each analysed set of fixture files, by paths.
     *
     * @var array<string, list<Error>>
     */
    private static array $fixtureErrors = [];

    /**
     * Asserts the line, identifier and message of every diagnostic of the files declaring the classes, in report order.
     *
     * @param string $method Method of the class whose lines the expectations locate.
     * @param list<array{string, string|null, string}> $expected Needle and optional start text of each reported line,
     * as {@see BehaviorFixture::line()} locates them, and its message.
     *
     * @phpstan-param class-string $class
     * @phpstan-param class-string ...$otherClasses
     */
    private function assertDiagnostics(string $class, string $method, array $expected, string ...$otherClasses): void
    {
        $actual = [];

        foreach ($this->classErrors($class, ...$otherClasses) as $error) {
            $actual[] = [
                $error->getLine(),
                $error->getIdentifier(),
                $error->getMessage(),
            ];
        }

        $expectedDiagnostics = [];

        foreach ($expected as [$needle, $start, $message]) {
            $expectedDiagnostics[] = [
                BehaviorFixture::line($class, $method, $needle, $start),
                self::IDENTIFIER,
                $message,
            ];
        }

        self::assertSame(
            $expectedDiagnostics,
            $actual,
            'Lines, identifiers and messages must match.',
        );
    }

    /**
     * Returns the diagnostics of the files that declare the fixture classes, analysed together on first use.
     *
     * @phpstan-param class-string ...$classes
     *
     * @return list<Error>
     */
    private function classErrors(string ...$classes): array
    {
        $files = [];

        foreach ($classes as $class) {
            $files[] = (string) (new ReflectionClass($class))->getFileName();
        }

        return self::$fixtureErrors[implode(',', $files)] ??= $this->gatherAnalyserErrors($files);
    }
}
