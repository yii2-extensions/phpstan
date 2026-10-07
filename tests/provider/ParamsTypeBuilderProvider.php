<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use stdClass;

use function fopen;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\ParamsTypeBuilderTest} test cases.
 */
final class ParamsTypeBuilderProvider
{
    /**
     * @return iterable<string, array{array<array-key, mixed>, string}>
     */
    public static function paramsProvider(): iterable
    {
        yield 'empty params' => [
            [],
            'array<mixed, mixed>',
        ];
        yield 'keys needing quoting' => [
            [
                'turnstile.siteKey' => '',
                "O'Reilly" => 'publisher',
                'C:\\path' => 'windows',
                'with space' => 1,
            ],
            "array{'turnstile.siteKey': string, \"O'Reilly\": string, 'C:\\path': string, 'with space': int}",
        ];
        yield 'list becomes positional shape' => [
            ['tags' => ['php', 'yii2']],
            'array{tags: array{string, string}}',
        ];
        yield 'mixed int and string keys' => [
            [
                'first',
                'second',
                10 => 'tenth',
                'adminEmail' => 'admin@example.com',
            ],
            'array{0: string, 1: string, 10: string, adminEmail: string}',
        ];
        yield 'nested shapes and nested empty arrays' => [
            [
                'mail' => ['from' => 'a@b.c', 'cc' => []],
                'empty' => [],
            ],
            'array{mail: array{from: string, cc: array<mixed, mixed>}, empty: array<mixed, mixed>}',
        ];
        yield 'non-scalar values become mixed' => [
            [
                'object' => new stdClass(),
                'closure' => static fn(): int => 1,
                'resource' => fopen('php://memory', 'r'),
            ],
            'array{object: mixed, closure: mixed, resource: mixed}',
        ];
        yield 'numeric-string keys' => [
            [
                '01' => 'zero-one',
                '1.5' => 'one-and-half',
                '7' => 'seven',
            ],
            "array{'01': string, '1.5': string, 7: string}",
        ];
        yield 'scalars are generalized' => [
            [
                'name' => 'app',
                'ttl' => 60,
                'ratio' => 1.5,
                'debug' => true,
                'none' => null,
            ],
            'array{name: string, ttl: int, ratio: float, debug: bool, none: null}',
        ];
        yield 'sparse int keys' => [
            ['sparse' => [2 => 'a', 5 => 'b']],
            'array{sparse: array{2: string, 5: string}}',
        ];
    }
}
