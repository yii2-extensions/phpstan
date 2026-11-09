<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\rules;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\Constant\{ConstantArrayTypeBuilder, ConstantIntegerType, ConstantStringType};
use PHPStan\Type\{Type, VerbosityLevel};
use PHPUnit\Framework\Attributes\{DataProviderExternal, RequiresPhp};
use yii2\extensions\phpstan\rules\{
    CreateObjectConfiguration,
    CreateObjectConfigurationResolver,
    CreateObjectConstructionContract,
};
use yii2\extensions\phpstan\ServiceMap;
use yii2\extensions\phpstan\ServiceMapResultCacheValueExtension;
use yii2\extensions\phpstan\tests\provider\CreateObjectConfigurationResolverProvider;
use yii2\extensions\phpstan\tests\support\createobject\Target;
use yii2\extensions\phpstan\tests\support\DependencyRecordingScopeTrait;

use function array_map;

use const DIRECTORY_SEPARATOR;

/**
 * Unit tests for the construction resolution and option types of {@see CreateObjectConfigurationResolver}, independently
 * of any rule boundary.
 *
 * {@see CreateObjectConfigurationResolverProvider} for test case data providers.
 */
final class CreateObjectConfigurationResolverTest extends PHPStanTestCase
{
    use DependencyRecordingScopeTrait;

    /**
     * Directory of the analysis configurations.
     */
    private const string CONFIG_PATH = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config'
        . DIRECTORY_SEPARATOR;

    /**
     * Line of the call, for options without their own line.
     */
    private const int LINE = 9;

    #[DataProviderExternal(CreateObjectConfigurationResolverProvider::class, 'constructLiteralProvider')]
    public function testLocateConfigurationArgumentOptionsAtTheirLiteralKey(
        Expr $arguments,
        Type $nativeType,
        int $expected,
    ): void {
        $scope = $this->recordingScope();

        $scope
            ->method('getNativeType')
            ->willReturn($nativeType);

        $configuration = $this
            ->resolver('create-object-config.php')
            ->resolveTypeOptions(
                'Yii',
                [['class', 2, new ConstantStringType(Target::class)], ['__construct()', 3, $arguments]],
                self::LINE,
                $scope,
            );

        self::assertSame(
            [['colour', $expected]],
            $configuration === null
                ? null
                : array_map(static fn(array $option): array => [$option[0], $option[1]], $configuration->options),
            'Argument option must be located at its key or at the call.',
        );
    }

    #[DataProviderExternal(CreateObjectConfigurationResolverProvider::class, 'expressionProvider')]
    public function testResolveClassValuesWrittenAsExpressions(Expr $class, Type $nativeType, string|null $expected): void
    {
        $scope = $this->recordingScope();

        // as in a method of `Target`, where `static` resolves to the class itself
        $scope
            ->method('resolveName')
            ->willReturnCallback(
                static fn(Name $name): string => $name->toLowerString() === 'static'
                    ? Target::class
                    : $name->toString(),
            );
        $scope
            ->method('getNativeType')
            ->willReturn($nativeType);

        $configuration = $this
            ->resolver('create-object-config.php')
            ->resolveTypeOptions(
                'Yii',
                [['class', 2, $class], ['name', 3, new ConstantStringType('x')]],
                self::LINE,
                $scope,
            );

        self::assertSame(
            $expected,
            $configuration?->classReflection->getName(),
            'Class value must be read as Yii selects it.',
        );

        if ($configuration !== null) {
            self::assertSame(
                ['name'],
                array_map(static fn(array $option): string => $option[0], $configuration->options),
                'Class key expression must be removed.',
            );
        }
    }

    public function testResolveConstructorArgumentsWrittenAsAnExpression(): void
    {
        $arguments = ConstantArrayTypeBuilder::createEmpty();

        $arguments->setOffsetValueType(new ConstantIntegerType(0), ConstantArrayTypeBuilder::createEmpty()->getArray());

        $scope = $this->recordingScope();

        $scope
            ->method('getNativeType')
            ->willReturn($arguments->getArray());

        $configuration = $this
            ->resolver('create-object-config.php')
            ->resolveTypeOptions(
                'Yii',
                [
                    ['class', 2, new ConstantStringType(Target::class)],
                    ['__construct()', 3, new Variable('arguments')],
                    ['name', 4, new ConstantStringType('x')],
                ],
                self::LINE,
                $scope,
            );

        self::assertSame(
            [Target::class, ['name']],
            $configuration === null
                ? null
                : [
                    $configuration->classReflection->getName(),
                    array_map(static fn(array $option): string => $option[0], $configuration->options),
                ],
            'Native type of the expression must address only `$config`.',
        );
    }

    /**
     * @phpstan-param list<array{string, Type}> $options
     * @phpstan-param array{string, list<array{string, int, string}>}|null $expected
     */
    #[DataProviderExternal(CreateObjectConfigurationResolverProvider::class, 'actionProvider')]
    public function testResolveExternalActionConstructionOnlyWithoutArgumentsOrCode(
        array $options,
        array|null $expected,
    ): void {
        $this->assertResolved(
            'external-action-config.php',
            $options,
            $expected,
            CreateObjectConstructionContract::externalAction(),
        );
    }

    /**
     * @phpstan-param list<array{string, Type}> $options
     * @phpstan-param array{string, list<array{string, int, string}>}|null $expected
     */
    #[DataProviderExternal(CreateObjectConfigurationResolverProvider::class, 'traitActionProvider')]
    #[RequiresPhp('>= 8.5.0')]
    public function testResolveExternalActionDefinitionsOnTraitRedeclaredProperties(
        array $options,
        array|null $expected,
    ): void {
        $this->assertResolved(
            'external-action-config.php',
            $options,
            $expected,
            CreateObjectConstructionContract::externalAction(),
        );
    }

    /**
     * @phpstan-param list<array{string, Type}> $options
     * @phpstan-param array{string, list<array{string, int, string}>}|null $expected
     */
    #[DataProviderExternal(CreateObjectConfigurationResolverProvider::class, 'traitDefinitionProvider')]
    #[RequiresPhp('>= 8.5.0')]
    public function testResolveNoDefinitionThatSetsATraitRedeclaredProperty(array $options, array|null $expected): void
    {
        $this->assertResolved(
            'create-object-config.php',
            $options,
            $expected,
        );
    }

    /**
     * @phpstan-param list<array{string, Type}> $options
     * @phpstan-param array{string, list<array{string, int, string}>}|null $expected
     */
    #[DataProviderExternal(CreateObjectConfigurationResolverProvider::class, 'hookedActionProvider')]
    #[RequiresPhp('>= 8.4.0')]
    public function testResolveNoExternalActionWithHookedIdOrController(array $options, array|null $expected): void
    {
        $this->assertResolved(
            'external-action-config.php',
            $options,
            $expected,
            CreateObjectConstructionContract::externalAction(),
        );
    }

    /**
     * @phpstan-param list<array{string, Type}> $options
     * @phpstan-param array{string, list<array{string, int, string}>}|null $expected
     */
    #[DataProviderExternal(CreateObjectConfigurationResolverProvider::class, 'hookedDefinitionProvider')]
    #[RequiresPhp('>= 8.4.0')]
    public function testResolveOnlyDefinitionsWhoseOptionsAreTrulyPlainProperties(
        array $options,
        array|null $expected,
    ): void {
        $this->assertResolved(
            'assignment-config.php',
            $options,
            $expected,
        );
    }

    /**
     * @phpstan-param list<array{string, Type}> $options
     * @phpstan-param array{string, list<array{string, int, string}>}|null $expected
     */
    #[DataProviderExternal(CreateObjectConfigurationResolverProvider::class, 'resolveProvider')]
    public function testResolveTheClassAndOptionsYiiAssigns(array $options, array|null $expected): void
    {
        $this->assertResolved(
            'create-object-config.php',
            $options,
            $expected,
        );
    }

    /**
     * @phpstan-param class-string $className
     * @phpstan-param list<array{string, Type}> $options
     * @phpstan-param list<array{string, int, string, string, string}> $expected
     */
    #[DataProviderExternal(CreateObjectConfigurationResolverProvider::class, 'rejectedProvider')]
    public function testReturnOptionValuesTheCreatedClassNeverAccepts(
        string $className,
        array $options,
        array $expected,
    ): void {
        $configuration = new CreateObjectConfiguration(
            'Yii',
            self::createReflectionProvider()->getClass($className),
            self::withLines($options),
        );

        self::assertSame(
            $expected,
            array_map(
                static fn(array $rejected): array => [
                    $rejected[0],
                    $rejected[1],
                    $rejected[2]->describe(VerbosityLevel::precise()),
                    $rejected[3]->describe(VerbosityLevel::precise()),
                    $rejected[4],
                ],
                CreateObjectConfigurationResolver::rejectedOptions($configuration, $this->recordingScope()),
            ),
            'Rejected options must match in configuration order.',
        );
    }

    public function testTrackConstructionAndCreatedClassBeforeDeciding(): void
    {
        $resolver = $this->resolver('create-object-config.php');

        $resolver->resolveTypeOptions(
            'Yii',
            self::withLines([['class', new ConstantStringType('binding.second')]]),
            self::LINE,
            $this->recordingScope(),
        );
        $resolver->resolveTypeOptions(
            'Yii',
            self::withLines([['class', new ConstantStringType('Missing\Target')]]),
            self::LINE,
            $this->recordingScope(),
        );
        $resolver->resolveTypeOptions(
            'Yii',
            self::withLines([['class', new ConstantStringType('binding.factory')]]),
            self::LINE,
            $this->recordingScope(),
        );

        self::assertSame(
            [
                [
                    ServiceMapResultCacheValueExtension::class,
                    ServiceMapResultCacheValueExtension::constructionKey('binding.second'),
                ],
                [
                    ServiceMapResultCacheValueExtension::class,
                    ServiceMapResultCacheValueExtension::constructionKey('Missing\Target'),
                ],
                [
                    ServiceMapResultCacheValueExtension::class,
                    ServiceMapResultCacheValueExtension::constructionKey('binding.factory'),
                ],
            ],
            $this->trackedValues,
            'Construction keys must be tracked, also when the resolver stands aside.',
        );
        self::assertSame(
            [Target::class, 'Missing\Target'],
            $this->trackedClasses,
            'Created class must be tracked once known, even when missing.',
        );

        $this->trackedValues = [];
        $this->trackedClasses = [];

        $resolver->resolveTypeOptions(
            'Yii',
            self::withLines([['name', new ConstantStringType('x')]]),
            self::LINE,
            $this->recordingScope(),
        );
        $resolver->resolveTypeOptions(
            'Yii',
            self::withLines([['class', new ConstantStringType('')]]),
            self::LINE,
            $this->recordingScope(),
        );

        self::assertSame(
            [],
            $this->trackedValues,
            'No class name means no construction dependency.',
        );
        self::assertSame(
            [],
            $this->trackedClasses,
            'No class name means no class dependency.',
        );
    }

    /**
     * @phpstan-param list<array{string, Type}> $options
     * @phpstan-param array{string, list<array{string, int, string}>}|null $expected
     */
    private function assertResolved(
        string $config,
        array $options,
        array|null $expected,
        CreateObjectConstructionContract|null $contract = null,
    ): void {
        $configuration = $this
            ->resolver($config)
            ->resolveTypeOptions(
                'Yii',
                self::withLines($options),
                self::LINE,
                $this->recordingScope(),
                $contract,
            );

        if ($configuration !== null) {
            self::assertSame(
                'Yii',
                $configuration->calledClass,
                'Called class must be kept.',
            );
        }

        self::assertSame(
            $expected,
            $configuration === null
                ? null
                : [
                    $configuration->classReflection->getName(),
                    array_map(
                        static fn(array $option): array => [
                            $option[0],
                            $option[1],
                            $option[2] instanceof Type
                                ? $option[2]->describe(VerbosityLevel::precise())
                                : 'expression',
                        ],
                        $configuration->options,
                    ),
                ],
            'Created class and assigned options must match.',
        );
    }

    private function resolver(string $config): CreateObjectConfigurationResolver
    {
        return new CreateObjectConfigurationResolver(
            self::createReflectionProvider(),
            new ServiceMap(self::CONFIG_PATH . $config),
        );
    }

    /**
     * Gives each option the line after the previous one, starting at line 2.
     *
     * @phpstan-param list<array{string, Type}> $options
     *
     * @return list<array{string, int, Type}>
     */
    private static function withLines(array $options): array
    {
        $lined = [];

        foreach ($options as $index => [$name, $value]) {
            $lined[] = [$name, $index + 2, $value];
        }

        return $lined;
    }
}
