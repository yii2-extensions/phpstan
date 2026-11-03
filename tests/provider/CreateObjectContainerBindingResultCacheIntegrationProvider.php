<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\CreateObjectContainerBindingResultCacheIntegrationTest} test
 * cases.
 */
final class CreateObjectContainerBindingResultCacheIntegrationProvider
{
    /**
     * Binds `Probe\Contract` to `Probe\Target` by a class string.
     */
    private const string ALIAS = "'Probe\\Contract' => 'Probe\\Target',";

    /**
     * Binds `Probe\Contract` to a typed factory of `Probe\Target` that ignores the configuration.
     */
    private const string FACTORY = "'Probe\\Contract' => static fn(): \\Probe\\Target => new \\Probe\\Target(),";

    /**
     * Binds `Probe\Contract` to the container ID `probe.hop`.
     */
    private const string HOP = "'Probe\\Contract' => 'probe.hop',";

    /**
     * Binds `probe.hop` to `Probe\Target` by a class string.
     */
    private const string HOP_ALIAS = "'probe.hop' => 'Probe\\Target',";

    /**
     * Binds `probe.hop` back to `Probe\Contract`, closing a cycle.
     */
    private const string HOP_CYCLE = "'probe.hop' => 'Probe\\Contract',";

    /**
     * Binds `probe.hop` to a typed factory of `Probe\Target` that ignores the configuration.
     */
    private const string HOP_FACTORY = "'probe.hop' => static fn(): \\Probe\\Target => new \\Probe\\Target(),";

    /**
     * Binds `Probe\Contract` to `Probe\Other`, which accepts both options.
     */
    private const string OTHER = "'Probe\\Contract' => 'Probe\\Other',";

    /**
     * @phpstan-return iterable<string, array{list<string>, list<string>, list<string>, list<string>, bool, bool}>
     */
    public static function definitionProvider(): iterable
    {
        yield 'alias to factory of the same class' => [
            [self::ALIAS],
            [],
            [self::FACTORY],
            [],
            true,
            false,
        ];
        yield 'factory to alias of the same class' => [
            [self::FACTORY],
            [],
            [self::ALIAS],
            [],
            false,
            true,
        ];
        yield 'alias to singleton of the same class' => [
            [self::ALIAS],
            [],
            [],
            [self::ALIAS],
            true,
            false,
        ];
        yield 'singleton to alias of the same class' => [
            [],
            [self::ALIAS],
            [self::ALIAS],
            [],
            false,
            true,
        ];
        yield 'target changed' => [
            [self::ALIAS],
            [],
            [self::OTHER],
            [],
            true,
            false,
        ];
        yield 'hop resolved to factory' => [
            [self::HOP, self::HOP_ALIAS],
            [],
            [self::HOP, self::HOP_FACTORY],
            [],
            true,
            false,
        ];
        yield 'hop closes a cycle' => [
            [self::HOP, self::HOP_ALIAS],
            [],
            [self::HOP, self::HOP_CYCLE],
            [],
            true,
            false,
        ];
        yield 'cycle resolved by the hop' => [
            [self::HOP, self::HOP_CYCLE],
            [],
            [self::HOP, self::HOP_ALIAS],
            [],
            false,
            true,
        ];
    }
}
