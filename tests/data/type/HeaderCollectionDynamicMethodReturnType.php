<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\type;

use PHPUnit\Framework\Attributes\TestWith;
use yii\web\HeaderCollection;

use function PHPStan\Testing\assertType;

/**
 * Test suite for dynamic return types of {@see HeaderCollection::get()} method in Yii HTTP header scenarios.
 *
 * Validates type inference and return types for the header collection {@see HeaderCollection::get()} method, covering
 * scenarios with different argument combinations, default values, first parameter variations, and various header
 * retrieval patterns.
 *
 * These tests ensure that PHPStan correctly infers the result types for HeaderCollection lookups, including string
 * returns, array returns, nullable returns, and union types based on method arguments.
 *
 * Key features:
 * - Array return type when first parameter is `false`.
 * - Dynamic return type inference based on the third argument (first parameter).
 * - Nullable return handling based on default value argument.
 * - String return type when first parameter is `true`.
 * - Union types when first parameter is indeterminate.
 *
 * @copyright Copyright (C) 2025 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class HeaderCollectionDynamicMethodReturnType
{
    public function testReturnArrayWhenFirstIsFalse(): void
    {
        $headers = new HeaderCollection();

        assertType('array<int, string>|null', $headers->get('Accept', null, false));
        assertType('array<int, string>', $headers->get('Accept', [], false));
        assertType('array<int, string>', $headers->get('Accept', 'default', false));
    }
    public function testReturnStringWhenFirstIsTrue(): void
    {
        $headers = new HeaderCollection();

        assertType('string|null', $headers->get('Content-Type'));
        assertType('string|null', $headers->get('Content-Type', null));
        assertType('string|null', $headers->get('Content-Type', null, true));
        assertType('string', $headers->get('Content-Type', 'default'));
        assertType('string', $headers->get('Content-Type', 'default', true));
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function testReturnUnionWhenFirstIsIndeterminate(bool $first): void
    {
        $headers = new HeaderCollection();

        assertType('array<int, string>|string|null', $headers->get('User-Agent', null, $first));
        assertType('array<int, string>|string', $headers->get('User-Agent', 'default', $first));
    }

    public function testReturnWithArrayDefaultAndFirstTrue(): void
    {
        $headers = new HeaderCollection();

        // when default is array but first is `true`, still returns `string`
        assertType('string', $headers->get('X-Forwarded-For', ['127.0.0.1'], true));
        assertType('array<int, string>', $headers->get('X-Forwarded-For', ['127.0.0.1'], false));
    }

    public function testReturnWithBooleanFirstParameter(): void
    {
        $headers = new HeaderCollection();

        // explicit `true`
        assertType('string|null', $headers->get('Content-Length', null, true));
        assertType('string', $headers->get('Content-Length', '0', true));

        // explicit `false`
        assertType('array<int, string>|null', $headers->get('Accept-Encoding', null, false));
        assertType('array<int, string>', $headers->get('Accept-Encoding', [], false));
    }

    public function testReturnWithComplexScenarios(): void
    {
        $headers = new HeaderCollection();

        // no arguments beyond name - should default to first=`true`
        assertType('string|null', $headers->get('Host'));

        // only name and default
        assertType('string', $headers->get('Referer', 'http://example.com'));
        assertType('string|null', $headers->get('Origin', null));

        // all three arguments with various combinations
        assertType('string', $headers->get('Accept-Language', 'en-US', true));
        assertType('array<int, string>', $headers->get('Accept-Charset', ['utf-8'], false));
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function testReturnWithDynamicBooleanFirstParameter(bool $first): void
    {
        $headers = new HeaderCollection();

        assertType('array<int, string>|string|null', $headers->get('Connection', null, $first));
        assertType('array<int, string>|string', $headers->get('Connection', 'keep-alive', $first));
    }

    public function testReturnWithExplicitNullDefault(): void
    {
        $headers = new HeaderCollection();

        assertType('string|null', $headers->get('Expires', null));
        assertType('string|null', $headers->get('Expires', null, true));
        assertType('array<int, string>|null', $headers->get('Expires', null, false));
    }

    #[TestWith(['string-default'])]
    #[TestWith([null])]
    public function testReturnWithMixedTypeDefault(string|null $default): void
    {
        $headers = new HeaderCollection();

        assertType('string|null', $headers->get('X-Request-ID', $default, true));
        assertType('array<int, string>|null', $headers->get('X-Request-ID', $default, false));
    }

    public function testReturnWithNonNullDefault(): void
    {
        $headers = new HeaderCollection();

        assertType('string', $headers->get('Authorization', 'Bearer token'));
        assertType('string', $headers->get('Authorization', 'Bearer token', true));
        assertType('array<int, string>', $headers->get('Authorization', ['Bearer token'], false));
    }

    #[TestWith(['fallback'])]
    #[TestWith([null])]
    public function testReturnWithNullableDefault(string|null $default): void
    {
        $headers = new HeaderCollection();

        assertType('string|null', $headers->get('X-Custom-Header', $default));
        assertType('string|null', $headers->get('X-Custom-Header', $default, true));
        assertType('array<int, string>|null', $headers->get('X-Custom-Header', $default, false));
    }

    public function testReturnWithVariableDefault(): void
    {
        $headers = new HeaderCollection();

        $defaultValue = 'fallback-value';

        assertType('string', $headers->get('Cache-Control', $defaultValue));
        assertType('string', $headers->get('Cache-Control', $defaultValue, true));
        assertType('array<int, string>', $headers->get('Cache-Control', $defaultValue, false));
    }
}
