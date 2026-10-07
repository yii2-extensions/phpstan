<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\web\data\type;

use yii\base\Module;

use function PHPStan\Testing\assertType;

/**
 * Module redeclaring `$params` for type assertions that keep its own PHPDoc type over the configured params type.
 */
final class ApplicationParamsOwnParamsModule extends Module
{
    /**
     * @var array<string, int>
     */
    public $params = [];

    public function testKeepRedeclaredParams(): void
    {
        assertType('array<string, int>', $this->params);
    }
}
