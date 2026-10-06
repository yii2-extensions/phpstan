<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\web\data\type;

use Countable;
use Yii;
use yii\base\{Application as BaseApplication, Module};
use yii\console\Application as ConsoleApplication;
use yii\web\Application as WebApplication;
use yii2\extensions\phpstan\tests\support\stub\ApplicationParamsOwner;

use function array_push;
use function is_int;
use function is_string;
use function PHPStan\Testing\assertType;

/**
 * Type assertion fixture for the stub-declared `Yii::$app` and `Module::$params` types, which PHPStan narrows,
 * assigns and merges natively.
 */
final class ApplicationParamsType
{
    public function testAssignedApplication(ConsoleApplication $console): void
    {
        Yii::$app = $console;

        assertType('yii\console\Application', Yii::$app);
    }

    public function testAssignedBaseApplication(BaseApplication $base): void
    {
        Yii::$app = $base;

        assertType('yii\base\Application', Yii::$app);
    }

    public function testAssignedNullableApplication(WebApplication|null $web): void
    {
        Yii::$app = $web;

        assertType('yii\web\Application|null', Yii::$app);
    }

    public function testAssignedParam(): void
    {
        Yii::$app->params['adminEmail'] = 1;

        assertType('1', Yii::$app->params['adminEmail']);
        assertType('int', Yii::$app->params['maxItems']);
    }

    /**
     * @param list<string> $values
     */
    public function testAssignedParamInForeach(array $values): void
    {
        foreach ($values as $value) {
            Yii::$app->params['maxItems'] = $value;
        }

        assertType('int', Yii::$app->params['maxItems']);
    }

    public function testAssignedParamInIfElseBranches(bool $condition): void
    {
        if ($condition) {
            Yii::$app->params['maxItems'] = 'x';
        } else {
            Yii::$app->params['maxItems'] = 5;
        }

        assertType("5|'x'", Yii::$app->params['maxItems']);
    }

    public function testAssignedUnionApplication(ConsoleApplication $console, WebApplication $web, bool $condition): void
    {
        Yii::$app = $condition ? $console : $web;

        assertType('yii\console\Application|yii\web\Application', Yii::$app);

        if (Yii::$app instanceof ConsoleApplication) {
            assertType('yii\console\Application', Yii::$app);
        }
    }

    public function testContradictoryNarrowingIsNever(): void
    {
        if (is_string(Yii::$app->params['adminEmail']) && is_int(Yii::$app->params['adminEmail'])) {
            assertType('*NEVER*', Yii::$app->params['adminEmail']);
        }
    }

    public function testIssetAndNullCoalesce(): void
    {
        $email = Yii::$app->params['adminEmail'] ?? 'default';

        assertType('string', $email);
        assertType('string', Yii::$app->params['nested']['key1'] ?? 'default');
        assertType("'default'", Yii::$app->params['nullableParam'] ?? 'default');
        assertType("'default'", Yii::$app->params['missing'] ?? 'default');
        assertType('bool', isset(Yii::$app->params['adminEmail']) ? Yii::$app->params['debugMode'] : false);
        assertType('int', Yii::$app->params['nested']['key2']);

        if (isset(Yii::$app->params['adminEmail'])) {
            assertType('string', Yii::$app->params['adminEmail']);
        }
    }

    public function testNarrowedApplication(): void
    {
        if (Yii::$app instanceof ConsoleApplication) {
            assertType('*NEVER*', Yii::$app);
        }
    }

    public function testNarrowedParams(): void
    {
        if (Yii::$app->params['adminEmail'] === 'admin@example.com') {
            assertType("'admin@example.com'", Yii::$app->params['adminEmail']);
        }

        if (is_string(Yii::$app->params['nested']['key1'])) {
            assertType('string', Yii::$app->params['nested']['key1']);
        }

        assertType('null', Yii::$app->params['nullableParam']);
    }

    public function testPushedListParam(): void
    {
        array_push(Yii::$app->params['tags'], 'z');

        assertType("array{string, string, 'z'}", Yii::$app->params['tags']);
    }

    public function testResolveApplicationThroughAnyBaseYiiSpelling(): void
    {
        assertType('yii\web\Application', \yii\BaseYii::$app);
        assertType('yii\web\Application', \Yii::$app);
    }

    public function testResolveParamsThroughApplicationVariable(): void
    {
        $app = Yii::$app;

        assertType('string', $app->params['adminEmail']);
    }

    public function testResolveParamsThroughIntersectionAndUnionOwners(
        Module&Countable $countable,
        Module|ApplicationParamsOwner $owner,
    ): void {
        assertType('int', $countable->params['maxItems']);
        assertType('int|string', $owner->params['maxItems']);
    }

    public function testResolveParamsThroughNullsafeModule(): void
    {
        assertType(
            "array{'turnstile.siteKey': string, adminEmail: string, maxItems: int, debugMode: bool, ratio: float, nullableParam: null, nested: array{key1: string, key2: int}, tags: array{string, string}}|null",
            Yii::$app->getModule('admin')?->params,
        );
    }
}
