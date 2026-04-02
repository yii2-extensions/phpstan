<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\custom\data\property;

use Yii;
use yii2\extensions\phpstan\tests\support\stub\ApplicationCustom;

use function PHPStan\Testing\assertType;

/**
 * Data provider for property reflection of a custom Yii Application in PHPStan analysis.
 *
 * Validates that `Yii::$app` resolves to the configured custom application type when a user-defined application class
 * is specified in the PHPStan configuration.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 0.4.1
 */
final class ApplicationPropertiesClassReflectionType
{
    public function testReturnCustomApplicationInstanceFromYiiApp(): void
    {
        assertType(ApplicationCustom::class, Yii::$app);
    }

    public function testReturnStringFromCharsetProperty(): void
    {
        assertType('string', Yii::$app->charset);
    }

    public function testReturnStringFromDefaultRouteProperty(): void
    {
        assertType('string', Yii::$app->defaultRoute);
    }

    public function testReturnStringFromLanguageProperty(): void
    {
        assertType('string', Yii::$app->language);
    }
}
