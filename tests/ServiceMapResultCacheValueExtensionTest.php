<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests;

use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use yii2\extensions\phpstan\{ParamsTypeBuilder, ServiceMap, ServiceMapResultCacheValueExtension};
use yii2\extensions\phpstan\tests\provider\ServiceMapResultCacheValueExtensionProvider;

/**
 * Unit tests for {@see ServiceMapResultCacheValueExtension} values compared by the PHPStan result cache.
 *
 * {@see ServiceMapResultCacheValueExtensionProvider} for test case data providers.
 */
final class ServiceMapResultCacheValueExtensionTest extends PHPStanTestCase
{
    /**
     * Base path for configuration files used in tests.
     */
    private const BASE_PATH = __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'result-cache'
        . DIRECTORY_SEPARATOR;

    #[DataProviderExternal(ServiceMapResultCacheValueExtensionProvider::class, 'changedValueProvider')]
    public function testGetValueChangesWhenRelevantEntryChanges(string $key, string $changedConfig): void
    {
        self::assertNotSame(
            self::createExtension('base')->getValue($key),
            self::createExtension($changedConfig)->getValue($key),
            'Value must change with the entry it covers.',
        );
    }

    public function testGetValueDistinguishesComponentStates(): void
    {
        $extension = self::createExtension('component-states');

        self::assertSame(
            ServiceMapResultCacheValueExtension::MISSING,
            $extension->getValue(ServiceMapResultCacheValueExtension::componentKey('absent')),
            'Absent component must map to the missing value.',
        );
        self::assertSame(
            ServiceMapResultCacheValueExtension::UNRESOLVED,
            $extension->getValue(ServiceMapResultCacheValueExtension::componentKey('untyped')),
            'Component with an unknown class must map to the unresolved value.',
        );
        self::assertNotContains(
            $extension->getValue(ServiceMapResultCacheValueExtension::componentKey('typed')),
            [ServiceMapResultCacheValueExtension::MISSING, ServiceMapResultCacheValueExtension::UNRESOLVED],
            'Resolved component must map to its class hash.',
        );
    }

    public function testGetValueDistinguishesParamsShapesWithSameDescription(): void
    {
        $joined = new ServiceMap(self::BASE_PATH . 'params-keys-joined.php');
        $split = new ServiceMap(self::BASE_PATH . 'params-keys-split.php');

        self::assertSame(
            ParamsTypeBuilder::build($joined->getParams())->describe(VerbosityLevel::precise()),
            ParamsTypeBuilder::build($split->getParams())->describe(VerbosityLevel::precise()),
            'Fixture shapes must share one unescaped description.',
        );

        $key = ServiceMapResultCacheValueExtension::PARAMS_KEY;

        self::assertNotSame(
            (new ServiceMapResultCacheValueExtension($joined))->getValue($key),
            (new ServiceMapResultCacheValueExtension($split))->getValue($key),
            'Distinct shapes must yield distinct values.',
        );
    }

    #[DataProviderExternal(ServiceMapResultCacheValueExtensionProvider::class, 'stableValueProvider')]
    public function testGetValueIsStableWhenUnrelatedEntryChanges(string $key, string $changedConfig): void
    {
        self::assertSame(
            self::createExtension('base')->getValue($key),
            self::createExtension($changedConfig)->getValue($key),
            'Value must ignore entries it does not cover.',
        );
    }

    public function testGetValueReturnsMissingForParamsWhenNoParamsAreConfigured(): void
    {
        $extension = new ServiceMapResultCacheValueExtension(new ServiceMap());

        self::assertSame(
            ServiceMapResultCacheValueExtension::MISSING,
            $extension->getValue(ServiceMapResultCacheValueExtension::PARAMS_KEY),
            'Empty params must map to the stable missing value.',
        );
    }

    #[DataProviderExternal(ServiceMapResultCacheValueExtensionProvider::class, 'missingKeyProvider')]
    public function testGetValueReturnsMissingForUnknownId(string $key): void
    {
        self::assertSame(
            ServiceMapResultCacheValueExtension::MISSING,
            self::createExtension('base')->getValue($key),
            'Unknown id must map to the stable missing value.',
        );
    }

    public function testGetValueReturnsSameParamsValueOnRepeatedCalls(): void
    {
        $extension = self::createExtension('base');

        self::assertSame(
            $extension->getValue(ServiceMapResultCacheValueExtension::PARAMS_KEY),
            $extension->getValue(ServiceMapResultCacheValueExtension::PARAMS_KEY),
            'Params value must be stable across calls.',
        );
    }

    #[DataProviderExternal(ServiceMapResultCacheValueExtensionProvider::class, 'unsupportedKeyProvider')]
    public function testGetValueReturnsUnsupportedForUnknownKey(string $key): void
    {
        self::assertSame(
            ServiceMapResultCacheValueExtension::UNSUPPORTED,
            self::createExtension('base')->getValue($key),
            'Unknown key must yield the sentinel instead of failing.',
        );
    }

    public function testKeyBuildersPrefixIdWithKind(): void
    {
        self::assertSame(
            'behaviors:app\models\Post',
            ServiceMapResultCacheValueExtension::behaviorsKey('app\models\Post'),
            'Prefix must be `behaviors`.',
        );
        self::assertSame(
            'component:user',
            ServiceMapResultCacheValueExtension::componentKey('user'),
            'Prefix must be `component`.',
        );
        self::assertSame(
            'service:mailer',
            ServiceMapResultCacheValueExtension::serviceKey('mailer'),
            'Prefix must be `service`.',
        );
    }

    public function testKeyRoundTripsThroughResultCache(): void
    {
        $extension = self::createExtension('base');

        $key = ServiceMapResultCacheValueExtension::componentKey('user');

        self::assertSame(
            $key,
            $extension->keyFromResultCache($extension->keyToResultCache($key)),
            'Stored key must restore the original key.',
        );
    }

    private static function createExtension(string $config): ServiceMapResultCacheValueExtension
    {
        return new ServiceMapResultCacheValueExtension(
            new ServiceMap(self::BASE_PATH . $config . '.php'),
            ['user' => 'identityClass'],
        );
    }
}
