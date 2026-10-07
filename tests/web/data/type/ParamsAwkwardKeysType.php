<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\web\data\type;

use Yii;

use function PHPStan\Testing\assertType;

/**
 * Type assertion fixture for params whose keys need quoting or escaping when written as a PHPDoc array shape.
 */
final class ParamsAwkwardKeysType
{
    public function testResolveEveryKey(): void
    {
        assertType('string', Yii::$app->params["it's"]);
        assertType('string', Yii::$app->params['back\\slash']);
        assertType('int', Yii::$app->params['quote"d']);
        assertType('string', Yii::$app->params['']);
        assertType('float', Yii::$app->params[' ']);
        assertType('bool', Yii::$app->params['a b']);
        assertType('int', Yii::$app->params["new\nline"]);
        assertType('null', Yii::$app->params["tab\tkey"]);
        assertType('int', Yii::$app->params['x*/y']);
        assertType('string', Yii::$app->params['a$b']);
        assertType('int', Yii::$app->params["c\x01"]);
        assertType('string', Yii::$app->params['é']);
        assertType('string', Yii::$app->params[-5]);
        assertType('string', Yii::$app->params[123]);
        assertType('int', Yii::$app->params['nested']['*/']);
        assertType('array{ok: bool}', Yii::$app->params['nested']["line\nbreak"]);
        assertType('array{string, string}', Yii::$app->params['list']);
    }
}
