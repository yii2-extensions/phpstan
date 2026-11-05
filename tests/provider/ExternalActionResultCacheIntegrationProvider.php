<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\ExternalActionResultCacheIntegrationTest} test cases.
 */
final class ExternalActionResultCacheIntegrationProvider
{
    /**
     * Binds `Probe\Target` to a factory that ignores the configuration.
     */
    private const string FACTORY = "'Probe\\Target' => static fn(): \\Probe\\Target => new \\Probe\\Target('run', null),";
    /**
     * Binds `Probe\Target` to `Probe\Other`, an action that accepts both options.
     */
    private const string OTHER = "'Probe\\Target' => 'Probe\\Other',";

    /**
     * @phpstan-return iterable<string, array{string, string, string, string}>
     */
    public static function boundClassProvider(): iterable
    {
        $colour = "    public string \$colour = '';\n";

        yield 'bound class loses a property' => [$colour, '', 'none', 'unknown'];
        yield 'bound class gains a property' => ['', $colour, 'unknown', 'none'];
    }

    /**
     * @phpstan-return iterable<string, array{list<string>, list<string>, string, string}>
     */
    public static function containerProvider(): iterable
    {
        yield 'unbound class becomes bound to another action' => [[], [self::OTHER], 'both', 'none'];
        yield 'bound class becomes unbound' => [[self::OTHER], [], 'none', 'both'];
        yield 'unbound class becomes a factory' => [[], [self::FACTORY], 'both', 'none'];
        yield 'factory becomes unbound' => [[self::FACTORY], [], 'none', 'both'];
    }

    /**
     * @phpstan-return iterable<string, array{string, string, string, string}>
     */
    public static function parentControllerProvider(): iterable
    {
        $override = "    public function createAction(\$id): \\yii\\base\\Action|null\n    {\n"
            . "        return parent::createAction(\$id);\n    }\n";

        yield 'parent starts overriding createAction()' => ['', $override, 'both', 'none'];
        yield 'parent stops overriding createAction()' => [$override, '', 'none', 'both'];
    }
}
