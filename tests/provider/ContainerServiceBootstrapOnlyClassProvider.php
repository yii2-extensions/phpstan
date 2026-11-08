<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\ContainerServiceBootstrapOnlyClassTest} test cases.
 */
final class ContainerServiceBootstrapOnlyClassProvider
{
    /**
     * Definition without content, which Yii turns into `['class' => $id]`.
     */
    private const string EMPTY_DEFINITION = '[]';

    /**
     * Definition with options but no `class` or `__class` key, which Yii completes with the namespaced ID.
     */
    private const string OPTIONS_DEFINITION = "['label' => 'configured']";

    /**
     * @phpstan-return iterable<string, array{string}>
     */
    public static function definitionProvider(): iterable
    {
        yield 'empty definition' => [
            self::EMPTY_DEFINITION,
        ];
        yield 'options without class' => [
            self::OPTIONS_DEFINITION,
        ];
    }

    /**
     * @phpstan-return iterable<string, array{string, array{service: null, unresolved: bool, value: string}}>
     */
    public static function preBootstrapAnswerProvider(): iterable
    {
        yield 'empty definition' => [
            self::EMPTY_DEFINITION,
            ['service' => null, 'unresolved' => false, 'value' => 'missing'],
        ];
        yield 'options without class' => [
            self::OPTIONS_DEFINITION,
            ['service' => null, 'unresolved' => true, 'value' => 'unresolved'],
        ];
    }

    /**
     * @phpstan-return iterable<string, array{string, list<array{string, int, string, string|null}>}>
     */
    public static function preBootstrapResultProvider(): iterable
    {
        yield 'empty definition' => [
            self::EMPTY_DEFINITION,
            [
                ['src/BootstrapOnlyConsumer.php', 11, 'phpstan.dumpType', 'Probe\BootstrapOnlyService'],
                ['src/BootstrapOnlyConsumer.php', 12, 'phpstan.dumpType', 'string'],
            ],
        ];
        yield 'options without class' => [
            self::OPTIONS_DEFINITION,
            [
                ['src/BootstrapOnlyConsumer.php', 11, 'phpstan.dumpType', 'object'],
                ['src/BootstrapOnlyConsumer.php', 12, 'method.notFound', null],
                ['src/BootstrapOnlyConsumer.php', 12, 'phpstan.dumpType', '*ERROR*'],
            ],
        ];
    }
}
