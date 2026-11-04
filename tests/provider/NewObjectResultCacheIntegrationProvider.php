<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\NewObjectResultCacheIntegrationTest} test cases.
 */
final class NewObjectResultCacheIntegrationProvider
{
    /**
     * Binds `Probe\Target` to `Probe\Bound`, which declares `colour`.
     */
    private const string BINDING = "'Probe\\Target' => 'Probe\\Bound',";
    /**
     * Setter for `colour` with an empty body.
     */
    private const string COLOUR_SETTER = "    public function setColour(string \$colour): void {}\n";
    /**
     * `int` property `count` of `Probe\Target`, which rejects the consumer's `'all'`.
     */
    private const string INT_COUNT = "    public int \$count = 0;\n";

    /**
     * @phpstan-return iterable<string, array{list<string>, list<string>, bool, bool}>
     */
    public static function bindingProvider(): iterable
    {
        yield 'binding removed' => [[self::BINDING], [], false, true];
        yield 'binding added' => [[], [self::BINDING], true, false];
    }

    /**
     * @phpstan-return iterable<string, array{string, string, string, string, int, array{bool, bool}, array{bool, bool}}>
     */
    public static function declarationProvider(): iterable
    {
        yield 'setter added to the class' => [
            '',
            '',
            self::INT_COUNT,
            self::INT_COUNT . self::COLOUR_SETTER,
            3,
            [true, true],
            [false, true],
        ];
        yield 'setter added to the parent class' => [
            '',
            self::COLOUR_SETTER,
            self::INT_COUNT,
            self::INT_COUNT,
            4,
            [true, true],
            [false, true],
        ];
        yield 'property type widened' => [
            '',
            '',
            self::INT_COUNT,
            "    public string \$count = '';\n",
            3,
            [true, true],
            [true, false],
        ];
        yield 'parent setter body only' => [
            self::COLOUR_SETTER,
            "    public function setColour(string \$colour): void\n    {\n        unset(\$colour);\n    }\n",
            self::INT_COUNT,
            self::INT_COUNT,
            1,
            [false, true],
            [false, true],
        ];
    }
}
