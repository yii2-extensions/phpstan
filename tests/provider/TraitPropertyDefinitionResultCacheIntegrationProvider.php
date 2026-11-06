<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\TraitPropertyDefinitionResultCacheIntegrationTest} test
 * cases.
 */
final class TraitPropertyDefinitionResultCacheIntegrationProvider
{
    /**
     * Property `trigger` without a hook, as `Probe\Base` declares it.
     */
    public const string PLAIN_TRIGGER = "    public mixed \$trigger = null;\n";

    /**
     * Property `trigger` with a set hook that attaches `Probe\Paint`, whose `colour` property receives a later option.
     */
    private const string PAINT_TRIGGER = "    public mixed \$trigger = null {\n"
        . "        set {\n"
        . "            \$this->attachBehavior('paint', new Paint());\n\n"
        . "            \$this->trigger = \$value;\n"
        . "        }\n"
        . "    }\n";

    /**
     * @phpstan-return iterable<string, array{string, string, bool, bool}>
     */
    public static function traitProvider(): iterable
    {
        yield 'trigger trait gains a set hook' => [self::PLAIN_TRIGGER, self::PAINT_TRIGGER, true, false];
        yield 'trigger trait loses its set hook' => [self::PAINT_TRIGGER, self::PLAIN_TRIGGER, false, true];
    }
}
