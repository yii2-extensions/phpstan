<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support;

use PHPStan\Analyser\Error;
use ReflectionClass;

use function dirname;

/**
 * Provides the diagnostic assertions of the `Yii::createObject()` and `new` rule tests, analysing each fixture once per
 * test class.
 */
trait CreateObjectRuleTestTrait
{
    /**
     * Diagnostics of each analysed fixture file, by path.
     *
     * @var array<string, list<Error>>
     */
    private static array $fixtureErrors = [];

    /**
     * Asserts the line, identifier and message of the diagnostics of one fixture method, in report order.
     *
     * @param list<array{string|null, string}> $expected Key each diagnostic is reported at, or `null` for the call
     * line, and its message.
     * @param string $call Text that starts the call, such as `createObject(` or `new `.
     *
     * @phpstan-param class-string $fixture
     */
    private function assertMethodDiagnostics(
        string $fixture,
        string $method,
        array $expected,
        string $call = 'createObject(',
    ): void {
        $actual = [];

        foreach ($this->fixtureErrors((string) (new ReflectionClass($fixture))->getFileName()) as $error) {
            if (FixtureMethod::at($fixture, $error->getLine()) === $method) {
                $actual[] = [$error->getLine(), $error->getIdentifier(), $error->getMessage()];
            }
        }

        $expectedDiagnostics = [];

        foreach ($expected as [$key, $message]) {
            $expectedDiagnostics[] = [
                FixtureMethod::line($fixture, $method, $key, $call),
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
     * Asserts that a data file produces no diagnostic.
     */
    private function assertNoDiagnostics(string $file): void
    {
        self::assertSame(
            [],
            $this->fixtureErrors(dirname(__DIR__) . '/data/rule/' . $file),
            'No diagnostic must be reported.',
        );
    }

    /**
     * Returns the diagnostics of a fixture file, analysing it on first use.
     *
     * @return list<Error>
     */
    private function fixtureErrors(string $file): array
    {
        return self::$fixtureErrors[$file] ??= $this->gatherAnalyserErrors([$file]);
    }
}
