<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\type;

use PHPUnit\Framework\Attributes\TestWith;
use yii\web\HeaderCollection;

use function PHPStan\Testing\assertType;

/**
 * Type assertion fixture for {@see HeaderCollection::get()} return types under Yii 22, which declares `false` for an
 * empty first value.
 *
 * Verifies type inference across positional and named argument combinations, returning `string|false` when the `$first`
 * parameter is `true`, an array when it is `false`, and a union when it is indeterminate, with `null` removed when the
 * default value cannot be `null`.
 */
final class HeaderCollectionDynamicMethodReturnTypeYii22
{
    public function testReturnArrayWhenFirstIsFalse(): void
    {
        $headers = new HeaderCollection();

        assertType(
            'array<string>|null',
            $headers->get('Accept', null, false),
        );
        assertType(
            'array<string>',
            $headers->get('Accept', [], false),
        );
        assertType(
            'array<string>',
            $headers->get('Accept', ['default'], false),
        );
    }

    /**
     * @param array{0: string|null} $arguments
     */
    #[TestWith([['fallback']])]
    #[TestWith([[null]])]
    public function testReturnDeclaredTypeWhenArgumentIsUnpacked(array $arguments): void
    {
        $headers = new HeaderCollection();

        assertType(
            'string|false|null',
            $headers->get('Accept', ...[null]),
        );
        assertType(
            'string|false|null',
            $headers->get('Accept', ...$arguments),
        );
    }

    public function testReturnStringWhenFirstIsTrue(): void
    {
        $headers = new HeaderCollection();

        assertType(
            'string|false|null',
            $headers->get('Content-Type'),
        );
        assertType(
            'string|false|null',
            $headers->get('Content-Type', null),
        );
        assertType(
            'string|false|null',
            $headers->get('Content-Type', null, true),
        );
        assertType(
            'string|false',
            $headers->get('Content-Type', 'default'),
        );
        assertType(
            'string|false',
            $headers->get('Content-Type', 'default', true),
        );
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function testReturnUnionWhenFirstIsIndeterminate(bool $first): void
    {
        $headers = new HeaderCollection();

        assertType(
            'array<string>|string|false|null',
            $headers->get('User-Agent', null, $first),
        );
        assertType(
            'array<string>|string|false',
            $headers->get('User-Agent', 'default', $first),
        );
    }

    public function testReturnWithArrayDefaultAndFirstTrue(): void
    {
        $headers = new HeaderCollection();

        // when default is array but first is `true`, still returns `string|false`
        assertType(
            'string|false',
            $headers->get('X-Forwarded-For', '127.0.0.1', true),
        );
        assertType(
            'array<string>',
            $headers->get('X-Forwarded-For', ['127.0.0.1'], false),
        );
    }

    public function testReturnWithBooleanFirstParameter(): void
    {
        $headers = new HeaderCollection();

        // explicit `true`
        assertType(
            'string|false|null',
            $headers->get('Content-Length', null, true),
        );
        assertType(
            'string|false',
            $headers->get('Content-Length', '0', true),
        );

        // explicit `false`
        assertType(
            'array<string>|null',
            $headers->get('Accept-Encoding', null, false),
        );
        assertType(
            'array<string>',
            $headers->get('Accept-Encoding', [], false),
        );
    }

    public function testReturnWithComplexScenarios(): void
    {
        $headers = new HeaderCollection();

        // no arguments beyond name - should default to first=`true`
        assertType(
            'string|false|null',
            $headers->get('Host'),
        );

        // only name and default
        assertType(
            'string|false',
            $headers->get('Referer', 'http://example.com'),
        );
        assertType(
            'string|false|null',
            $headers->get('Origin', null),
        );

        // all three arguments with various combinations
        assertType(
            'string|false',
            $headers->get('Accept-Language', 'en-US', true),
        );
        assertType(
            'array<string>',
            $headers->get('Accept-Charset', ['utf-8'], false),
        );
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function testReturnWithDynamicBooleanFirstParameter(bool $first): void
    {
        $headers = new HeaderCollection();

        assertType(
            'array<string>|string|false|null',
            $headers->get('Connection', null, $first),
        );
        assertType(
            'array<string>|string|false',
            $headers->get('Connection', 'keep-alive', $first),
        );
    }

    public function testReturnWithExplicitNullDefault(): void
    {
        $headers = new HeaderCollection();

        assertType(
            'string|false|null',
            $headers->get('Expires', null),
        );
        assertType(
            'string|false|null',
            $headers->get('Expires', null, true),
        );
        assertType(
            'array<string>|null',
            $headers->get('Expires', null, false),
        );
    }

    #[TestWith(['string-default'])]
    #[TestWith([null])]
    public function testReturnWithMixedTypeDefault(string|null $default): void
    {
        $headers = new HeaderCollection();

        assertType(
            'string|false|null',
            $headers->get('X-Request-ID', $default, true),
        );
    }

    public function testReturnWithNamedArguments(): void
    {
        $headers = new HeaderCollection();

        assertType(
            'array<string>|null',
            $headers->get('Accept', first: false),
        );
        assertType(
            'array<string>|null',
            $headers->get(first: false, name: 'Accept'),
        );
        assertType(
            'string|false',
            $headers->get('Accept', default: 'text/html'),
        );
        assertType(
            'string|false',
            $headers->get(name: 'Accept', first: true, default: 'text/html'),
        );
        assertType(
            'array<string>',
            $headers->get('Accept', default: ['text/html'], first: false),
        );
    }

    public function testReturnWithNonNullDefault(): void
    {
        $headers = new HeaderCollection();

        assertType(
            'string|false',
            $headers->get('Authorization', 'Bearer token'),
        );
        assertType(
            'string|false',
            $headers->get('Authorization', 'Bearer token', true),
        );
        assertType(
            'array<string>',
            $headers->get('Authorization', ['Bearer token'], false),
        );
    }

    #[TestWith(['fallback'])]
    #[TestWith([null])]
    public function testReturnWithNullableDefault(string|null $default): void
    {
        $headers = new HeaderCollection();

        assertType(
            'string|false|null',
            $headers->get('X-Custom-Header', $default),
        );
        assertType(
            'string|false|null',
            $headers->get('X-Custom-Header', $default, true),
        );
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function testReturnWithNullableDefaultVariable(bool $useFallback): void
    {
        $headers = new HeaderCollection();

        $default = $useFallback ? 'fallback' : null;
        $defaults = $useFallback ? ['fallback'] : null;

        assertType(
            'string|false|null',
            $headers->get('X-Custom-Header', $default),
        );
        assertType(
            'string|false|null',
            $headers->get('X-Custom-Header', default: $default),
        );
        assertType(
            'array<string>|null',
            $headers->get('X-Custom-Header', $defaults, false),
        );
    }

    public function testReturnWithVariableDefault(): void
    {
        $headers = new HeaderCollection();

        $defaultValue = 'fallback-value';

        assertType(
            'string|false',
            $headers->get('Cache-Control', $defaultValue),
        );
        assertType(
            'string|false',
            $headers->get('Cache-Control', $defaultValue, true),
        );
        assertType(
            'array<string>',
            $headers->get('Cache-Control', [$defaultValue], false),
        );
    }
}
