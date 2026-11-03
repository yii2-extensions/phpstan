<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\CreateObjectComponentOptionResultCacheIntegrationTest} test
 * cases.
 */
final class CreateObjectComponentOptionResultCacheIntegrationProvider
{
    /**
     * @phpstan-return iterable<string, array{string, array{string, bool, bool, string}, array{string, bool, bool, string}, int}>
     */
    public static function changeProvider(): iterable
    {
        yield 'behavior map gains the class' => [
            'behaviors.php',
            ['Probe\Placeholder', false, false, 'int'],
            ['Probe\Target', false, false, 'int'],
            2,
        ];
        yield 'behavior map loses the class' => [
            'behaviors.php',
            ['Probe\Target', false, false, 'int'],
            ['Probe\Placeholder', false, false, 'int'],
            2,
        ];
        yield 'behavior map gains the parent class' => [
            'behaviors.php',
            ['Probe\Placeholder', false, false, 'int'],
            ['Probe\Base', false, false, 'int'],
            2,
        ];
        yield 'class overrides behaviors()' => [
            'src/Target.php',
            ['Probe\Placeholder', false, false, 'int'],
            ['Probe\Placeholder', false, true, 'int'],
            3,
        ];
        yield 'class stops overriding behaviors()' => [
            'src/Target.php',
            ['Probe\Placeholder', false, true, 'int'],
            ['Probe\Placeholder', false, false, 'int'],
            3,
        ];
        yield 'parent class overrides behaviors()' => [
            'src/Base.php',
            ['Probe\Placeholder', false, false, 'int'],
            ['Probe\Placeholder', true, false, 'int'],
            4,
        ];
        yield 'native property type accepts the value' => [
            'src/Target.php',
            ['Probe\Placeholder', false, false, 'int'],
            ['Probe\Placeholder', false, false, 'string'],
            3,
        ];
    }
}
