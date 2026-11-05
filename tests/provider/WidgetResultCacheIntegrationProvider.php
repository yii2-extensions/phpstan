<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\WidgetResultCacheIntegrationTest} test cases.
 */
final class WidgetResultCacheIntegrationProvider
{
    /**
     * Binds `Probe\Target` to a typed factory that ignores the configuration.
     */
    private const string FACTORY = "'Probe\\Target' => static fn(): \\Probe\\Target => new \\Probe\\Target(),";
    /**
     * Binds `Probe\Target` to `Probe\Other`, a widget that accepts both options.
     */
    private const string OTHER = "'Probe\\Target' => 'Probe\\Other',";
    /**
     * Overrides `widget()` in `Probe\Target`, forwarding the configuration.
     */
    private const string OVERRIDE = "    /** @param array<mixed> \$config */\n"
        . "    public static function widget(\$config = []): string\n    {\n"
        . "        return parent::widget(\$config);\n    }\n";
    /**
     * Same override with another body.
     */
    private const string OVERRIDE_BODY = "    /** @param array<mixed> \$config */\n"
        . "    public static function widget(\$config = []): string\n    {\n"
        . "        return parent::widget(\$config) . '';\n    }\n";
    /**
     * Binds `Probe\Target` to `Probe\Plain`, a configurable object that isn't a widget.
     */
    private const string PLAIN = "'Probe\\Target' => 'Probe\\Plain',";

    /**
     * @phpstan-return iterable<string, array{list<string>, list<string>, bool, bool}>
     */
    public static function containerProvider(): iterable
    {
        yield 'unbound class becomes bound to another widget' => [
            [],
            [self::OTHER],
            true,
            false,
        ];
        yield 'bound class becomes unbound' => [
            [self::OTHER],
            [],
            false,
            true,
        ];
        yield 'unbound class becomes a factory' => [
            [],
            [self::FACTORY],
            true,
            false,
        ];
        yield 'factory becomes unbound' => [
            [self::FACTORY],
            [],
            false,
            true,
        ];
        yield 'unbound class becomes bound to a non-widget' => [
            [],
            [self::PLAIN],
            true,
            false,
        ];
        yield 'non-widget binding becomes unbound' => [
            [self::PLAIN],
            [],
            false,
            true,
        ];
    }

    /**
     * @phpstan-return iterable<string, array{list<string>, string, string, string, string, int, string, string}>
     */
    public static function declarationProvider(): iterable
    {
        $colour = "    public string \$colour = '';\n";

        yield 'called class gains a setter' => [
            [],
            '',
            "    public function setColour(string \$colour): void {}\n",
            $colour,
            $colour,
            2,
            'both',
            'type',
        ];
        yield 'called class overrides widget()' => [
            [],
            '',
            self::OVERRIDE,
            $colour,
            $colour,
            2,
            'both',
            'none',
        ];
        yield 'called class stops overriding widget()' => [
            [],
            self::OVERRIDE,
            '',
            $colour,
            $colour,
            2,
            'none',
            'both',
        ];
        yield 'override body only' => [
            [],
            self::OVERRIDE,
            self::OVERRIDE_BODY,
            $colour,
            $colour,
            1,
            'none',
            'none',
        ];
        yield 'bound class loses a property' => [
            [self::OTHER],
            '',
            '',
            $colour,
            '',
            2,
            'none',
            'unknown',
        ];
        yield 'bound class gains a property' => [
            [self::OTHER],
            '',
            '',
            '',
            $colour,
            2,
            'unknown',
            'none',
        ];
    }
}
