<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\TraitPropertyResultCacheIntegrationTest} test cases.
 */
final class TraitPropertyResultCacheIntegrationProvider
{
    /**
     * `int` property `count` without a hook, as `Probe\Base` declares it.
     */
    public const string PLAIN_COUNT = "    public int \$count = 0;\n";
    /**
     * Protected property `secret`, as `Probe\Base` declares it.
     */
    public const string PROTECTED_SECRET = "    protected string \$secret = '';\n";
    /**
     * Public property `secret`, which PHP 8.5 lets the trait redeclare.
     */
    private const string PUBLIC_SECRET = "    public string \$secret = '';\n";
    /**
     * `int` property `count` with a set hook that also accepts the consumer's string.
     */
    private const string WIDE_COUNT = "    public int \$count = 0 {\n"
        . "        set(int|string \$value) => (int) \$value;\n    }\n";

    /**
     * @phpstan-return iterable<string, array{string, string, string, list<'count'|'secret'>, list<'count'|'secret'>}>
     */
    public static function traitProvider(): iterable
    {
        yield 'count trait gains a wider set hook' => [
            'CountTrait',
            self::PLAIN_COUNT,
            self::WIDE_COUNT,
            ['count', 'secret'],
            ['secret'],
        ];
        yield 'count trait loses its set hook' => [
            'CountTrait',
            self::WIDE_COUNT,
            self::PLAIN_COUNT,
            ['secret'],
            ['count', 'secret'],
        ];
        yield 'secret trait makes the property public' => [
            'SecretTrait',
            self::PROTECTED_SECRET,
            self::PUBLIC_SECRET,
            ['count', 'secret'],
            ['count'],
        ];
        yield 'secret trait makes the property protected' => [
            'SecretTrait',
            self::PUBLIC_SECRET,
            self::PROTECTED_SECRET,
            ['count'],
            ['count', 'secret'],
        ];
    }
}
