<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\web\data\type;

use yii\base\Module;

use function PHPStan\Testing\assertType;

/**
 * Module reading and assigning its inherited `$params` property for configured params type assertions.
 */
final class ApplicationParamsModule extends Module
{
    public function testAssignedOwnParams(Module $module): void
    {
        $this->params = $module->params;

        assertType(
            "array{'turnstile.siteKey': string, adminEmail: string, maxItems: int, debugMode: bool, ratio: float, nullableParam: null, nested: array{key1: string, key2: int}, tags: array{string, string}}",
            $this->params,
        );

        $this->params = ['adminEmail' => 'x'];

        assertType(
            "array{adminEmail: 'x'}",
            $this->params,
        );
    }

    public function testIssetAndNullCoalesceOwnParams(): void
    {
        assertType(
            'true',
            isset($this->params['nested']['key1']),
        );
        assertType(
            'false',
            isset($this->params['nested']['missing']),
        );
        assertType(
            'false',
            isset($this->params['nullableParam']),
        );
        assertType(
            'false',
            isset($this->params['missing']),
        );
        assertType(
            "'default'",
            $this->params['nested']['missing'] ?? 'default',
        );
        assertType(
            "'default'",
            $this->params['nullableParam'] ?? 'default',
        );
        assertType(
            'int',
            $this->params['nested']['key2'] ?? 0,
        );
    }

    public function testResolveOwnParams(): void
    {
        assertType(
            'array{string, string}',
            $this->params['tags'],
        );
        assertType(
            'array{key1: string, key2: int}',
            $this->params['nested'],
        );
        assertType(
            'string',
            $this->params['nested']['key1'],
        );
    }
}
