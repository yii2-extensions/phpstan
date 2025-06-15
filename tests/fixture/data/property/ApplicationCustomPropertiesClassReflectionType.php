<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\fixture\data\property;

use Yii;
use yii\base\InvalidConfigException;
use yii2\extensions\phpstan\tests\stub\ApplicationCustom;

use function PHPStan\Testing\assertType;

/**
 * Data provider for property and method type reflection of custom Yii Application in PHPStan analysis.
 *
 * Validates type inference and return types for properties accessed on {@see ApplicationCustom}, ensuring that PHPStan
 * correctly recognizes and infers types for native and virtual properties as used in custom application context.
 *
 * These tests cover scenarios including direct property access, virtual property access, and assignment to global
 * `Yii::$app`, verifying that type assertions match the expected return types for each case.
 *
 * Key features.
 * - Assignment and type assertion for global `Yii::$app`.
 * - Ensures compatibility with PHPStan property reflection for custom Yii Application classes.
 * - Type assertion for native and virtual properties.
 * - Validates correct type inference for all supported property types.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class ApplicationCustomPropertiesClassReflectionType
{
    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     */
    public function testReturnApplicationInstanceFromConstructor(): void
    {
        $app = new ApplicationCustom(['id' => 'testApp']);

        assertType(ApplicationCustom::class, $app);

        Yii::$app = $app;

        assertType(ApplicationCustom::class, Yii::$app);
    }

    /**
     * @throws InvalidConfigException if the configuration is invalid or incomplete.
     */
    public function testReturnStringFromVirtualProperty(): void
    {
        $app = new ApplicationCustom(['id' => 'testApp']);

        assertType('string', $app->virtualProperty);

        Yii::$app = $app;

        assertType('string', Yii::$app->virtualProperty);
    }
}
