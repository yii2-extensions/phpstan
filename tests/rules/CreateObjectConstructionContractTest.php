<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\rules;

use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use ReflectionClass;
use ReflectionMethod;
use yii\base\{Action, Controller};
use yii2\extensions\phpstan\rules\{CreateObjectConstructionContract, ObjectOptionAssignment};
use yii2\extensions\phpstan\tests\provider\CreateObjectConstructionContractProvider;

use function array_map;

/**
 * Unit tests for {@see CreateObjectConstructionContract} and its use by {@see ObjectOptionAssignment::isConfigurable()}.
 *
 * {@see CreateObjectConstructionContractProvider} for test case data providers.
 */
final class CreateObjectConstructionContractTest extends PHPStanTestCase
{
    /**
     * @phpstan-param class-string $className
     */
    #[DataProviderExternal(CreateObjectConstructionContractProvider::class, 'classProvider')]
    public function testAcceptOnlyClassesInheritingTheContractConstructor(
        string $className,
        bool $baseObject,
        bool $externalAction,
    ): void {
        $classReflection = self::createReflectionProvider()->getClass($className);

        self::assertSame(
            $baseObject,
            ObjectOptionAssignment::isConfigurable(
                $classReflection,
                CreateObjectConstructionContract::baseObject(),
            ),
            'BaseObject contract acceptance must match.',
        );
        self::assertSame(
            $baseObject,
            ObjectOptionAssignment::isConfigurable($classReflection),
            'Default contract must be the BaseObject contract.',
        );
        self::assertSame(
            $externalAction,
            ObjectOptionAssignment::isConfigurable(
                $classReflection,
                CreateObjectConstructionContract::externalAction(),
            ),
            'External action contract acceptance must match.',
        );
    }

    public function testDescribeBaseObjectConstruction(): void
    {
        $contract = CreateObjectConstructionContract::baseObject();

        self::assertSame(
            ['yii\base\BaseObject', [], 0, 'config', true, []],
            [
                $contract->constructorClass,
                $contract->fixedArgumentPositions,
                $contract->configurationPosition,
                $contract->configurationName,
                $contract->followsConstructorArguments,
                $contract->propertiesWrittenBeforeConfiguration,
            ],
            'Profile must match the BaseObject constructor policy.',
        );
    }

    public function testDescribeExternalActionConstruction(): void
    {
        $contract = CreateObjectConstructionContract::externalAction();

        self::assertSame(
            [Action::class, [0, 1], 2, 'config', false, ['id', 'controller']],
            [
                $contract->constructorClass,
                $contract->fixedArgumentPositions,
                $contract->configurationPosition,
                $contract->configurationName,
                $contract->followsConstructorArguments,
                $contract->propertiesWrittenBeforeConfiguration,
            ],
            'Profile must match the arguments of `Controller::createAction()`.',
        );
    }

    public function testMatchInstalledActionConstructorAndCreateAction(): void
    {
        $constructor = new ReflectionMethod(Action::class, '__construct');

        self::assertSame(
            Action::class,
            $constructor->getDeclaringClass()->getName(),
            'Action must declare its constructor.',
        );
        self::assertSame(
            ['id', 'controller', 'config'],
            array_map(static fn($parameter): string => $parameter->getName(), $constructor->getParameters()),
            'Configuration must be the last of the three parameters.',
        );

        // Yii 22 declares `setModule()` and optional `$id` and `$controller`; Yii 2.0.55 neither
        $isYii22 = (new ReflectionClass(Action::class))->hasMethod('setModule');

        self::assertSame(
            $isYii22 ? 0 : 2,
            $constructor->getNumberOfRequiredParameters(),
            'Required parameters must match the installed Yii line.',
        );
        self::assertSame(
            Controller::class,
            (new ReflectionMethod(Controller::class, 'createAction'))->getDeclaringClass()->getName(),
            'Controller must declare `createAction()`.',
        );
    }
}
