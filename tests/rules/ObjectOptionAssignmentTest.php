<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\rules;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\{ObjectType, Type, VerbosityLevel};
use PHPUnit\Framework\Attributes\{DataProviderExternal, RequiresPhp};
use yii2\extensions\phpstan\rules\ObjectOptionAssignment;
use yii2\extensions\phpstan\ServiceMap;
use yii2\extensions\phpstan\ServiceMapResultCacheValueExtension;
use yii2\extensions\phpstan\tests\provider\ObjectOptionAssignmentProvider;
use yii2\extensions\phpstan\tests\support\createobject\{
    ComponentBehaviorsTarget,
    ComponentMappedChildTarget,
    ComponentMembersTarget,
    Target,
    TypedContract,
    TypedTarget,
    TypedValue,
};
use yii2\extensions\phpstan\tests\support\DependencyRecordingScopeTrait;

use function array_map;
use function array_values;

use const DIRECTORY_SEPARATOR;

/**
 * Unit tests for the shared option semantics of {@see ObjectOptionAssignment}, independently of any rule boundary.
 *
 * {@see ObjectOptionAssignmentProvider} for test case data providers.
 */
final class ObjectOptionAssignmentTest extends PHPStanTestCase
{
    use DependencyRecordingScopeTrait;

    /**
     * Configuration with the behavior map and container of the `Yii::createObject()` rule tests.
     */
    private const string CONFIG = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config'
        . DIRECTORY_SEPARATOR . 'create-object-config.php';

    /**
     * @phpstan-param class-string $className
     */
    #[DataProviderExternal(ObjectOptionAssignmentProvider::class, 'redeclarationProvider')]
    public function testDetectNoTraitRedeclarationOfPlainDeclarations(string $className, string $name, bool $expected): void
    {
        self::assertRedeclaration(
            $className,
            $name,
            $expected,
        );
    }

    /**
     * @phpstan-param class-string $className
     */
    #[DataProviderExternal(ObjectOptionAssignmentProvider::class, 'traitRedeclarationProvider')]
    #[RequiresPhp('>= 8.5')]
    public function testDetectTraitRedeclarationWithOtherModifiers(string $className, string $name, bool $expected): void
    {
        self::assertRedeclaration(
            $className,
            $name,
            $expected,
        );
    }

    /**
     * @phpstan-param array{string, string}|null $expected
     */
    #[DataProviderExternal(ObjectOptionAssignmentProvider::class, 'hookedTargetProvider')]
    #[RequiresPhp('>= 8.4')]
    public function testReturnNoTypeTargetForHookedOrAsymmetricVisibilityProperties(
        string $className,
        string $name,
        Type $actualType,
        array|null $expected,
    ): void {
        $this->assertTarget(
            $className,
            $name,
            $actualType,
            $expected,
        );
    }

    /**
     * @phpstan-param class-string $className
     * @phpstan-param array{string, string}|null $expected
     */
    #[DataProviderExternal(ObjectOptionAssignmentProvider::class, 'traitTargetProvider')]
    #[RequiresPhp('>= 8.5')]
    public function testReturnNoTypeTargetForPropertiesTraitsRedeclareDifferently(
        string $className,
        string $name,
        Type $actualType,
        array|null $expected,
    ): void {
        $this->assertTarget(
            $className,
            $name,
            $actualType,
            $expected,
        );
    }

    /**
     * @phpstan-param class-string $className
     * @phpstan-param list<string> $names
     * @phpstan-param list<string> $expected
     */
    #[DataProviderExternal(ObjectOptionAssignmentProvider::class, 'unknownOptionProvider')]
    public function testReturnOnlyOptionsTheClassCannotAssign(string $className, array $names, array $expected): void
    {
        $this->assertUnknownOptions(
            $className,
            $names,
            $expected,
        );
    }

    /**
     * @phpstan-param list<string> $names
     * @phpstan-param list<string> $expected
     */
    #[DataProviderExternal(ObjectOptionAssignmentProvider::class, 'hookedUnknownOptionProvider')]
    #[RequiresPhp('>= 8.4')]
    public function testReturnOnlyOptionsTheClassCannotAssignWithHooksOrAsymmetricVisibility(
        string $className,
        array $names,
        array $expected,
    ): void {
        $this->assertUnknownOptions(
            $className,
            $names,
            $expected,
        );
    }

    /**
     * @phpstan-param class-string $className
     * @phpstan-param list<string> $names
     * @phpstan-param list<string> $expected
     */
    #[DataProviderExternal(ObjectOptionAssignmentProvider::class, 'traitUnknownOptionProvider')]
    #[RequiresPhp('>= 8.5')]
    public function testReturnOnlyOptionsTheClassCannotAssignWithTraitRedeclarations(
        string $className,
        array $names,
        array $expected,
    ): void {
        $this->assertUnknownOptions(
            $className,
            $names,
            $expected,
        );
    }

    /**
     * @phpstan-param class-string $className
     * @phpstan-param array{string, string}|null $expected
     */
    #[DataProviderExternal(ObjectOptionAssignmentProvider::class, 'targetProvider')]
    public function testReturnTheMemberWhoseNativeTypeRejectsTheValue(
        string $className,
        string $name,
        Type $actualType,
        array|null $expected,
    ): void {
        $this->assertTarget(
            $className,
            $name,
            $actualType,
            $expected,
        );
    }

    public function testReturnUnknownOptionsWithTheirReportedLine(): void
    {
        self::assertSame(
            [['colour', 7], ['shade', 9]],
            ObjectOptionAssignment::unknownOptions(
                self::createReflectionProvider()->getClass(Target::class),
                [['colour', 7, null], ['name', 8, null], ['shade', 9, null]],
                new ServiceMap(self::CONFIG),
                $this->recordingScope(),
            ),
            'Each unknown option must keep its line.',
        );
    }

    public function testTrackBehaviorsOfEveryAncestorOnlyWhenTheMapDecides(): void
    {
        $classReflection = self::createReflectionProvider()->getClass(ComponentMappedChildTarget::class);
        $serviceMap = new ServiceMap(self::CONFIG);

        ObjectOptionAssignment::unknownOptions(
            $classReflection,
            [['colour', 1, null]],
            $serviceMap,
            $this->recordingScope(),
        );

        self::assertSame(
            array_map(
                static fn(ClassReflection $ancestor): array => [
                    ServiceMapResultCacheValueExtension::class,
                    ServiceMapResultCacheValueExtension::behaviorsKey($ancestor->getName()),
                ],
                array_values($classReflection->getAncestors()),
            ),
            $this->trackedValues,
            'Every ancestor behaviors key must be tracked.',
        );

        $this->trackedValues = [];

        ObjectOptionAssignment::unknownOptions(
            self::createReflectionProvider()->getClass(ComponentMembersTarget::class),
            [['colour', 1, null], ['as paint', 2, null]],
            $serviceMap,
            $this->recordingScope(),
        );
        ObjectOptionAssignment::unknownOptions(
            self::createReflectionProvider()->getClass(ComponentBehaviorsTarget::class),
            [['colour', 1, null]],
            $serviceMap,
            $this->recordingScope(),
        );
        ObjectOptionAssignment::unknownOptions(
            self::createReflectionProvider()->getClass(Target::class),
            [['colour', 1, null]],
            $serviceMap,
            $this->recordingScope(),
        );

        self::assertSame(
            [],
            $this->trackedValues,
            'Behavior option, overridden `behaviors()` and BaseObject must not read the map.',
        );
        self::assertSame(
            [],
            $this->trackedClasses,
            'Unknown options must not track classes.',
        );
    }

    public function testTrackClassesOfBothTypesOnlyWhenATypeTargetExists(): void
    {
        $classReflection = self::createReflectionProvider()->getClass(TypedTarget::class);

        $target = ObjectOptionAssignment::rejectingTarget(
            $classReflection,
            'value',
            new ObjectType(TypedContract::class),
            $this->recordingScope(),
        );

        self::assertNotNull(
            $target,
            'Interface must be rejected by the final class type.',
        );
        self::assertSame(
            [TypedValue::class, TypedContract::class],
            $this->trackedClasses,
            'Classes of the expected and actual types must be tracked.',
        );

        $this->trackedClasses = [];

        ObjectOptionAssignment::rejectingTarget(
            $classReflection,
            'colour',
            new ObjectType(TypedContract::class),
            $this->recordingScope(),
        );
        ObjectOptionAssignment::rejectingTarget(
            $classReflection,
            'frozen',
            new ConstantStringType(TypedContract::class),
            $this->recordingScope(),
        );

        self::assertSame(
            [],
            $this->trackedClasses,
            'No type target means no class dependency.',
        );
        self::assertSame(
            [],
            $this->trackedValues,
            'Type targets must not track values.',
        );
    }

    /**
     * @phpstan-param class-string $className
     */
    private static function assertRedeclaration(string $className, string $name, bool $expected): void
    {
        self::assertSame(
            $expected,
            ObjectOptionAssignment::isRedeclaredByTrait(
                self::createReflectionProvider()->getClass($className),
                $name,
            ),
            'Trait redeclaration must match the declarations.',
        );
    }

    /**
     * @phpstan-param array{string, string}|null $expected
     */
    private function assertTarget(string $className, string $name, Type $actualType, array|null $expected): void
    {
        $target = ObjectOptionAssignment::rejectingTarget(
            self::createReflectionProvider()->getClass($className),
            $name,
            $actualType,
            $this->recordingScope(),
        );

        self::assertSame(
            $expected,
            $target === null ? null : [$target[0]->describe(VerbosityLevel::precise()), $target[1]],
            'Rejecting type and member must match.',
        );
    }

    /**
     * @phpstan-param list<string> $names
     * @phpstan-param list<string> $expected
     */
    private function assertUnknownOptions(string $className, array $names, array $expected): void
    {
        $options = [];

        foreach ($names as $index => $name) {
            $options[] = [$name, $index + 1, null];
        }

        self::assertSame(
            $expected,
            array_map(
                static fn(array $option): string => $option[0],
                ObjectOptionAssignment::unknownOptions(
                    self::createReflectionProvider()->getClass($className),
                    $options,
                    new ServiceMap(self::CONFIG),
                    $this->recordingScope(),
                ),
            ),
            'Unknown options must match in configuration order.',
        );
    }
}
