<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\type;

use yii\log\{FileTarget, Logger, Target};
use yii2\extensions\phpstan\tests\support\stub\MessageContainer;

use function PHPStan\Testing\{assertSuperType, assertType};

/**
 * Type assertion fixture for Yii logger message inference.
 */
final class LoggerMessagesExpressionTypeResolver
{
    public function testLoggerMessagesExposeTheRuntimeTupleShape(): void
    {
        $logger = new Logger();

        assertType(
            'array<int|string, array{0: array<mixed>|string|Throwable|yii\\log\\PsrMessage, 1: int, 2: string, '
            . '3: float, 4: list<array{file: string, line: int, function?: string, class?: class-string, type?: string}>, '
            . '5?: int}>',
            $logger->messages,
        );

        foreach ($logger->messages as $message) {
            assertSuperType(
                'list<mixed>',
                $message,
            );
            assertType(
                'array<mixed>|string|Throwable|yii\\log\\PsrMessage',
                $message[0],
            );
            assertType(
                'int',
                $message[1],
            );
            assertType(
                'string',
                $message[2],
            );
            assertType(
                'float',
                $message[3],
            );
            assertType(
                'list<array{file: string, line: int, function?: string, class?: class-string, type?: string}>',
                $message[4],
            );
            assertType(
                'int|null',
                $message[5] ?? null,
            );
        }
    }

    public function testTargetFilteringDefersToTheDeclaredTypeWhenArgumentIsUnpacked(): void
    {
        // the declared type follows the Yii PHPDoc; a resolved call would be `array{}`, making the comparison `true`
        assertSuperType(
            'array',
            Target::filterMessages(...[[]]),
        );
        assertType(
            'bool',
            Target::filterMessages(...[[]]) === [],
        );
    }

    public function testTargetFilteringKeepsAnEmptyMessageArray(): void
    {
        assertType(
            'array{}',
            Target::filterMessages([]),
        );
    }

    public function testTargetMessagesAndFilteringPreserveTheTupleShape(): void
    {
        $logger = new Logger();
        $target = new FileTarget();

        assertType(
            'list<array{0: array<mixed>|string|Throwable|yii\\log\\PsrMessage, 1: int, 2: string, 3: float, '
            . '4: list<array{file: string, line: int, function?: string, class?: class-string, type?: string}>, 5?: int}>',
            $target->messages,
        );
        assertType(
            'array<int|string, array{0: array<mixed>|string|Throwable|yii\\log\\PsrMessage, 1: int, 2: string, '
            . '3: float, 4: list<array{file: string, line: int, function?: string, class?: class-string, type?: string}>, '
            . '5?: int}>',
            Target::filterMessages($logger->messages),
        );

        foreach ($target->messages as $message) {
            assertSuperType(
                'list<mixed>',
                $message,
            );
        }
    }

    public function testUnrelatedExpressionsKeepTheirNativeTypes(): void
    {
        $logger = new Logger();
        $container = new MessageContainer();

        assertType(
            'int',
            $logger->flushInterval,
        );
        assertType(
            'array<mixed>',
            $container->messages,
        );
    }
}
