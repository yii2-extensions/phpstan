<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\console\data\property;

use Yii;
use yii\console\Application;

use function PHPStan\Testing\assertType;

final class ApplicationPropertiesClassReflectionType
{
    public function testReturnApplicationInstanceFromYiiApp(): void
    {
        assertType(Application::class, Yii::$app);
    }
}
