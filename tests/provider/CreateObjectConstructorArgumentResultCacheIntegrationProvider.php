<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\CreateObjectConstructorArgumentResultCacheIntegrationTest}
 * test cases.
 */
final class CreateObjectConstructorArgumentResultCacheIntegrationProvider
{
    /**
     * Binds `Probe\Contract` to `Probe\Target` with an array `__construct()` option holding one argument.
     */
    private const string CONSTRUCT_ONE = "'Probe\\Contract' => ['class' => 'Probe\\Target', '__construct()' => [['name' => 'd']]],";
    /**
     * Binds `Probe\Contract` to `Probe\Target` with an array `__construct()` option holding two arguments.
     */
    private const string CONSTRUCT_TWO = "'Probe\\Contract' => ['class' => 'Probe\\Target', '__construct()' => [['name' => 'd'], 'x']],";
    /**
     * Binds `Probe\Contract` to the container ID `probe.hop`.
     */
    private const string HOP = "'Probe\\Contract' => 'probe.hop',";
    /**
     * Binds `probe.hop` to `Probe\Target` by a class string.
     */
    private const string HOP_ALIAS = "'probe.hop' => 'Probe\\Target',";
    /**
     * Binds `probe.hop` to `Probe\Target` with a constructor param after `$config`.
     */
    private const string HOP_SECOND_PARAM = "'probe.hop' => [['class' => 'Probe\\Target'], [1 => 'x']],";
    /**
     * Binds `Probe\Contract` to `Probe\Target` with one constructor param named `config`.
     */
    private const string PARAMS_NAMED = "'Probe\\Contract' => [['class' => 'Probe\\Target'], ['config' => ['name' => 'd']]],";
    /**
     * Binds `Probe\Contract` to `Probe\Target` with one positional constructor param.
     */
    private const string PARAMS_ONE = "'Probe\\Contract' => [['class' => 'Probe\\Target'], [['name' => 'd']]],";
    /**
     * Binds `Probe\Contract` to `Probe\Target` with the plain public property `name` as option and one positional param.
     */
    private const string PARAMS_ONE_WITH_OPTION = "'Probe\\Contract' => [['class' => 'Probe\\Target', 'name' => 'd'], [['name' => 'd']]],";
    /**
     * Binds `Probe\Contract` to `Probe\Target` with two positional constructor params.
     */
    private const string PARAMS_TWO = "'Probe\\Contract' => [['class' => 'Probe\\Target'], [['name' => 'd'], 'x']],";

    /**
     * @phpstan-return iterable<string, array{string, string, int, bool, bool}>
     */
    public static function classProvider(): iterable
    {
        yield 'method body only' => [
            "    public function init(): void\n    {\n        parent::init();\n    }\n",
            "    public function init(): void\n    {\n        parent::init();\n        \$this->name = 'init';\n    }\n",
            1,
            true,
            true,
        ];
        yield 'custom constructor added' => [
            '',
            "    public function __construct(array \$config = [])\n    {\n        parent::__construct(\$config);\n    }\n",
            4,
            true,
            false,
        ];
        yield 'custom constructor removed' => [
            "    public function __construct(array \$config = [])\n    {\n        parent::__construct(\$config);\n    }\n",
            '',
            4,
            false,
            true,
        ];
    }

    /**
     * @phpstan-return iterable<string, array{string, string, bool, bool}>
     */
    public static function constantProvider(): iterable
    {
        yield 'second argument added' => [
            "[['colour' => 'red']]",
            "[['colour' => 'red'], 'second']",
            true,
            false,
        ];
        yield 'second argument removed' => [
            "[['colour' => 'red'], 'second']",
            "[['colour' => 'red']]",
            false,
            true,
        ];
        yield 'positional to named argument' => [
            "[['colour' => 'red']]",
            "['config' => ['colour' => 'red']]",
            true,
            true,
        ];
        yield 'named argument renamed' => [
            "['config' => ['colour' => 'red']]",
            "['options' => ['colour' => 'red']]",
            true,
            false,
        ];
    }

    /**
     * @phpstan-return iterable<string, array{list<string>, list<string>, array{bool, bool}, array{bool, bool}}>
     */
    public static function definitionProvider(): iterable
    {
        yield 'one param to two params' => [
            [self::PARAMS_ONE],
            [self::PARAMS_TWO],
            [true, true],
            [false, false],
        ];
        yield 'two params to one param' => [
            [self::PARAMS_TWO],
            [self::PARAMS_ONE],
            [false, false],
            [true, true],
        ];
        yield 'positional to named param' => [
            [self::PARAMS_ONE],
            [self::PARAMS_NAMED],
            [true, true],
            [true, false],
        ];
        yield 'named to positional param' => [
            [self::PARAMS_NAMED],
            [self::PARAMS_ONE],
            [true, false],
            [true, true],
        ];
        yield 'definition gains an option' => [
            [self::PARAMS_ONE],
            [self::PARAMS_ONE_WITH_OPTION],
            [true, true],
            [true, false],
        ];
        yield 'hop gains a second param' => [
            [self::HOP, self::HOP_ALIAS],
            [self::HOP, self::HOP_SECOND_PARAM],
            [true, true],
            [false, false],
        ];
        yield 'params to __construct()' => [
            [self::PARAMS_ONE],
            [self::CONSTRUCT_ONE],
            [true, true],
            [true, true],
        ];
        yield '__construct() gains a second argument' => [
            [self::CONSTRUCT_ONE],
            [self::CONSTRUCT_TWO],
            [true, true],
            [false, false],
        ];
    }
}
