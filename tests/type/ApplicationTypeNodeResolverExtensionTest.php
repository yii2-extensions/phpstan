<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\type;

use PHPStan\Analyser\{DeclarationDependencyTracker, NameScope};
use PHPStan\PhpDocParser\Ast\Type\{IdentifierTypeNode, TypeNode};
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use yii\BaseYii;
use yii2\extensions\phpstan\{ServiceMap, ServiceMapResultCacheValueExtension};
use yii2\extensions\phpstan\tests\provider\ApplicationTypeNodeResolverExtensionProvider;
use yii2\extensions\phpstan\tests\support\stub\RecordingDeclarationDependencyTracker;
use yii2\extensions\phpstan\type\ApplicationTypeNodeResolverExtension;

use function dirname;
use function file_get_contents;

/**
 * Unit tests for {@see ApplicationTypeNodeResolverExtension} resolution of the placeholder types of the shipped stub.
 *
 * {@see ApplicationTypeNodeResolverExtensionProvider} for test case data providers.
 */
final class ApplicationTypeNodeResolverExtensionTest extends PHPStanTestCase
{
    /**
     * Base path for configuration files used in tests.
     */
    private const BASE_PATH = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config'
        . DIRECTORY_SEPARATOR;

    #[DataProviderExternal(ApplicationTypeNodeResolverExtensionProvider::class, 'applicationTypeProvider')]
    public function testResolveApplicationPlaceholderToConfiguredClass(string $config, string $expected): void
    {
        $type = self::resolve($config, ApplicationTypeNodeResolverExtension::APPLICATION_TYPE);

        self::assertSame(
            $expected,
            $type,
            'Type must be the configured application class.',
        );
    }

    public function testResolveParamsPlaceholderToConfiguredShape(): void
    {
        self::assertSame(
            'array{emptyList: array<mixed, mixed>, siteName: string, sparse: array{2: string, 5: string}, '
            . "\"O'Reilly\": string, 'C:\\path': string, resource: mixed}",
            self::resolve('params-empty-array-config.php', ApplicationTypeNodeResolverExtension::PARAMS_TYPE),
            'Type must be the shape built from the configured params.',
        );
    }

    public function testResolveParamsPlaceholderToPlainArrayWhenNoParamsAreConfigured(): void
    {
        self::assertSame(
            'array',
            self::resolve('', ApplicationTypeNodeResolverExtension::PARAMS_TYPE),
            'Missing params must keep the plain `array` type.',
        );
    }

    #[DataProviderExternal(ApplicationTypeNodeResolverExtensionProvider::class, 'unrelatedTypeNodeProvider')]
    public function testResolveReturnsNullForUnrelatedTypeNode(TypeNode $typeNode): void
    {
        $tracker = new RecordingDeclarationDependencyTracker();
        $extension = self::createExtension(new ServiceMap(self::BASE_PATH . 'params-config.php'), $tracker);

        self::assertNull(
            $extension->resolve($typeNode, new NameScope(null, [], BaseYii::class)),
            'Node must be left to PHPStan.',
        );
        self::assertSame(
            [],
            $tracker->dependencies,
            'No dependency must be recorded.',
        );
    }

    public function testResolveReturnsSameTypeInstanceOnRepeatedCalls(): void
    {
        $extension = self::createExtension(new ServiceMap(self::BASE_PATH . 'params-config.php'));

        $nameScope = new NameScope(null, []);
        $applicationNode = new IdentifierTypeNode(ApplicationTypeNodeResolverExtension::APPLICATION_TYPE);
        $paramsNode = new IdentifierTypeNode(ApplicationTypeNodeResolverExtension::PARAMS_TYPE);

        self::assertSame(
            $extension->resolve($applicationNode, $nameScope),
            $extension->resolve($applicationNode, $nameScope),
            'Application type must be built once.',
        );
        self::assertSame(
            $extension->resolve($paramsNode, $nameScope),
            $extension->resolve($paramsNode, $nameScope),
            'Params type must be built once.',
        );
    }

    public function testResolveSkipsTrackingOutsideClassScope(): void
    {
        $tracker = new RecordingDeclarationDependencyTracker();

        $extension = self::createExtension(new ServiceMap(self::BASE_PATH . 'params-config.php'), $tracker);

        $extension->resolve(
            new IdentifierTypeNode(ApplicationTypeNodeResolverExtension::PARAMS_TYPE),
            new NameScope(null, []),
        );

        self::assertSame([], $tracker->dependencies, 'No declaring class means no dependency.');
    }

    #[DataProviderExternal(ApplicationTypeNodeResolverExtensionProvider::class, 'trackedPlaceholderProvider')]
    public function testResolveTracksPlaceholderOnDeclaringClass(string $name, string $className, string $key): void
    {
        $tracker = new RecordingDeclarationDependencyTracker();

        $extension = self::createExtension(new ServiceMap(self::BASE_PATH . 'params-config.php'), $tracker);

        $nameScope = new NameScope(null, [], $className);

        $extension->resolve(new IdentifierTypeNode($name), $nameScope);
        $extension->resolve(new IdentifierTypeNode($name), $nameScope);

        self::assertSame(
            [
                [$className, ServiceMapResultCacheValueExtension::class, $key],
                [$className, ServiceMapResultCacheValueExtension::class, $key],
            ],
            $tracker->dependencies,
            'Every resolution must record the configuration key on the declaring class.',
        );
    }

    public function testStubDeclaresPlaceholderTypes(): void
    {
        $stub = (string) file_get_contents(dirname(__DIR__, 2) . '/stubs/yii.stub');

        self::assertStringContainsString(
            '@var ' . ApplicationTypeNodeResolverExtension::APPLICATION_TYPE . "\n",
            $stub,
            'Stub must declare `Yii::$app` with the application placeholder.',
        );
        self::assertStringContainsString(
            '@var ' . ApplicationTypeNodeResolverExtension::PARAMS_TYPE . "\n",
            $stub,
            'Stub must declare `Module::$params` with the params placeholder.',
        );
    }

    /**
     * Creates the extension under test with PHPStan's reflection provider and the given dependency tracker.
     */
    private static function createExtension(
        ServiceMap $serviceMap,
        DeclarationDependencyTracker|null $tracker = null,
    ): ApplicationTypeNodeResolverExtension {
        return new ApplicationTypeNodeResolverExtension(
            $serviceMap,
            self::createReflectionProvider(),
            $tracker ?? new RecordingDeclarationDependencyTracker(),
        );
    }

    /**
     * Resolves the given placeholder type for the given configuration file, and returns its precise description.
     */
    private static function resolve(string $config, string $name): string|null
    {
        $serviceMap = new ServiceMap($config !== '' ? self::BASE_PATH . $config : '');

        return self::createExtension($serviceMap)
            ->resolve(new IdentifierTypeNode($name), new NameScope(null, []))
            ?->describe(VerbosityLevel::precise());
    }
}
