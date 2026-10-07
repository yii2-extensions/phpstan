<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use PHPStan\PhpDocParser\Ast\Type\{IdentifierTypeNode, NullableTypeNode, TypeNode};
use yii\base\Module;
use yii\BaseYii;
use yii2\extensions\phpstan\ServiceMapResultCacheValueExtension;
use yii2\extensions\phpstan\type\ApplicationTypeNodeResolverExtension;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\type\ApplicationTypeNodeResolverExtensionTest} test cases.
 */
final class ApplicationTypeNodeResolverExtensionProvider
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function applicationTypeProvider(): iterable
    {
        yield 'base application' => [
            'phpstan-base-app-config.php',
            'yii\base\Application',
        ];
        yield 'console application' => [
            'phpstan-console-config.php',
            'yii\console\Application',
        ];
        yield 'custom application' => [
            'phpstan-custom-app-config.php',
            'yii2\extensions\phpstan\tests\support\stub\ApplicationCustom',
        ];
        yield 'default application' => [
            '',
            'yii\web\Application',
        ];
        yield 'global namespace application' => [
            'phpstan-global-class-app-config.php',
            'GlobalApplication',
        ];
        yield 'web application' => [
            'phpstan-config.php',
            'yii\web\Application',
        ];
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function trackedPlaceholderProvider(): iterable
    {
        yield 'application' => [
            ApplicationTypeNodeResolverExtension::APPLICATION_TYPE,
            BaseYii::class,
            ServiceMapResultCacheValueExtension::APPLICATION_KEY,
        ];
        yield 'params' => [
            ApplicationTypeNodeResolverExtension::PARAMS_TYPE,
            Module::class,
            ServiceMapResultCacheValueExtension::PARAMS_KEY,
        ];
    }

    /**
     * @return iterable<string, array{TypeNode}>
     */
    public static function unrelatedTypeNodeProvider(): iterable
    {
        yield 'class name' => [
            new IdentifierTypeNode('yii\web\Application'),
        ];
        yield 'native type' => [
            new IdentifierTypeNode('array'),
        ];
        yield 'nullable placeholder' => [
            new NullableTypeNode(new IdentifierTypeNode(ApplicationTypeNodeResolverExtension::PARAMS_TYPE)),
        ];
    }
}
