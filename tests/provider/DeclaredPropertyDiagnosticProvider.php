<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\DeclaredPropertyDiagnosticTest} test cases.
 *
 * Provides the analysis configuration of each application kind and the diagnostics expected on the shared fixture, as
 * line and identifier pairs.
 */
final class DeclaredPropertyDiagnosticProvider
{
    /**
     * @phpstan-return iterable<string, array{string, list<array{int, string}>}>
     */
    public static function applicationKindProvider(): iterable
    {
        yield 'console application' => [
            'phpstan-console-analysis-config.php',
            [
                [19, 'nullCoalesce.property'],
                [24, 'isset.property'],
                [34, 'instanceof.alwaysTrue'],
                [38, 'deadCode.unreachable'],
                [43, 'instanceof.alwaysFalse'],
                [52, 'nullCoalesce.offset'],
                [57, 'assign.propertyType'],
            ],
        ];
        yield 'web application' => [
            'phpstan-params-config.php',
            [
                [19, 'nullCoalesce.property'],
                [24, 'isset.property'],
                [34, 'instanceof.alwaysFalse'],
                [43, 'instanceof.alwaysTrue'],
                [47, 'deadCode.unreachable'],
                [52, 'nullCoalesce.offset'],
                [57, 'assign.propertyType'],
            ],
        ];
    }
}
