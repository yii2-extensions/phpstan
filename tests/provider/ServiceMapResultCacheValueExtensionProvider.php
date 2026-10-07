<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\provider;

use yii2\extensions\phpstan\ServiceMapResultCacheValueExtension;

/**
 * Data provider for {@see \yii2\extensions\phpstan\tests\ServiceMapResultCacheValueExtensionTest} test cases.
 */
final class ServiceMapResultCacheValueExtensionProvider
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function changedValueProvider(): iterable
    {
        yield 'application type' => [
            ServiceMapResultCacheValueExtension::APPLICATION_KEY,
            'application-changed',
        ];
        yield 'behavior order' => [
            ServiceMapResultCacheValueExtension::behaviorsKey('app\models\Post'),
            'behaviors-changed',
        ];
        yield 'component class' => [
            ServiceMapResultCacheValueExtension::componentKey('cache'),
            'component-class-changed',
        ];
        yield 'component generic' => [
            ServiceMapResultCacheValueExtension::componentKey('user'),
            'component-generic-changed',
        ];
        yield 'definition class' => [
            ServiceMapResultCacheValueExtension::serviceKey('mailer'),
            'service-changed',
        ];
        yield 'params type' => [
            ServiceMapResultCacheValueExtension::PARAMS_KEY,
            'params-type-changed',
        ];
        yield 'singleton class' => [
            ServiceMapResultCacheValueExtension::serviceKey('queue'),
            'service-changed',
        ];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function missingKeyProvider(): iterable
    {
        yield 'behaviors' => [ServiceMapResultCacheValueExtension::behaviorsKey('app\models\Comment')];
        yield 'component' => [ServiceMapResultCacheValueExtension::componentKey('missing')];
        yield 'service' => [ServiceMapResultCacheValueExtension::serviceKey('missing')];
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function stableValueProvider(): iterable
    {
        yield 'application type' => [
            ServiceMapResultCacheValueExtension::APPLICATION_KEY,
            'component-class-changed',
        ];
        yield 'closure definition' => [
            ServiceMapResultCacheValueExtension::serviceKey('closure'),
            'service-changed',
        ];
        yield 'initialized component' => [
            ServiceMapResultCacheValueExtension::componentKey('initialized'),
            'service-changed',
        ];
        yield 'other class behaviors' => [
            ServiceMapResultCacheValueExtension::behaviorsKey('app\models\User'),
            'behaviors-changed',
        ];
        yield 'other component' => [
            ServiceMapResultCacheValueExtension::componentKey('user'),
            'component-class-changed',
        ];
        yield 'params values of same type' => [
            ServiceMapResultCacheValueExtension::PARAMS_KEY,
            'params-value-changed',
        ];
        yield 'params' => [
            ServiceMapResultCacheValueExtension::PARAMS_KEY,
            'service-changed',
        ];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unsupportedKeyProvider(): iterable
    {
        yield 'empty key' => [''];
        yield 'unknown kind with id' => ['unknown:id'];
        yield 'unknown kind without id' => ['unknown'];
    }
}
