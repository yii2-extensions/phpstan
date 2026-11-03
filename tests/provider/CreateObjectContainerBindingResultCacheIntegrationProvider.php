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
     * Binds `probe.hop` to `Probe\Target` with the option `size`, which `Probe\Target` doesn't declare.
     */
    private const string HOP_UNKNOWN_OPTION = "'probe.hop' => ['class' => 'Probe\\Target', 'size' => 1],";
    /**
     * Binds `Probe\Contract` to `Probe\Target` with the plain public property `name` as option.
     */
    private const string OPTIONS = "'Probe\\Contract' => ['class' => 'Probe\\Target', 'name' => 'definition'],";
    /**
     * Binds `Probe\Contract` to `Probe\Other`, which accepts both options.
     */
    private const string OTHER = "'Probe\\Contract' => 'Probe\\Other',";
    /**
     * Binds `Probe\Contract` to `Probe\Target` with the option `tag`, a property or setter of `Probe\Target`.
     */
    private const string TAG_OPTION = "'Probe\\Contract' => ['class' => 'Probe\\Target', 'tag' => 'definition'],";
    /**
     * Binds `Probe\Contract` to `Probe\Target` with the option `size`, which `Probe\Target` doesn't declare.
     */
    private const string UNKNOWN_OPTION = "'Probe\\Contract' => ['class' => 'Probe\\Target', 'size' => 1],";

    /**
     * @phpstan-return iterable<string, array{string, string, string, int, bool, bool}>
     */
    public static function classProvider(): iterable
    {
        yield 'method body only' => [
            self::OPTIONS,
            "    public function init(): void\n    {\n        parent::init();\n    }\n",
            "    public function init(): void\n    {\n        parent::init();\n        \$this->name = 'init';\n    }\n",
            1,
            true,
            true,
        ];
        yield 'definition option property becomes setter' => [
            self::TAG_OPTION,
            "    public string \$tag = '';\n",
            "    public function setTag(string \$tag): void {}\n",
            2,
            true,
            false,
        ];
        yield 'definition option setter becomes property' => [
            self::TAG_OPTION,
            "    public function setTag(string \$tag): void {}\n",
            "    public string \$tag = '';\n",
            2,
            false,
            true,
        ];
    }

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
        yield 'alias gains plain property options' => [
            [self::ALIAS],
            [],
            [self::OPTIONS],
            [],
            true,
            true,
        ];
        yield 'plain property options to unknown option' => [
            [self::OPTIONS],
            [],
            [self::UNKNOWN_OPTION],
            [],
            true,
            false,
        ];
        yield 'unknown option to plain property options' => [
            [self::UNKNOWN_OPTION],
            [],
            [self::OPTIONS],
            [],
            false,
            true,
        ];
        yield 'hop gains unknown option' => [
            [self::HOP, self::HOP_ALIAS],
            [],
            [self::HOP, self::HOP_UNKNOWN_OPTION],
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
