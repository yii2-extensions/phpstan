<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\rules;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use Throwable;
use Yii;
use yii2\extensions\phpstan\rules\{NewObjectInvalidOptionTypeRule, NewObjectUnknownOptionRule};
use yii2\extensions\phpstan\tests\data\rule\{NewObjectUnknownOption, NewObjectValidOption};
use yii2\extensions\phpstan\tests\provider\NewObjectRuntimeProvider;
use yii2\extensions\phpstan\tests\support\createobject\{AliasTarget, Target};
use yii2\extensions\phpstan\tests\support\{CreateObjectContainerTrait, FixtureMethod};

use function sprintf;

/**
 * Runtime tests for the direct `new` outcomes of the {@see NewObjectUnknownOptionRule} and
 * {@see NewObjectInvalidOptionTypeRule} fixtures, with the container bindings of the analysis configuration in place.
 *
 * {@see NewObjectRuntimeProvider} for test case data providers.
 */
final class NewObjectRuntimeTest extends TestCase
{
    use CreateObjectContainerTrait;

    public function testIgnoreContainerBindingOfTheConstructedClass(): void
    {
        self::assertInstanceOf(
            Target::class,
            Yii::createObject(AliasTarget::class),
            'Container must bind the class name for `createObject()`.',
        );
        self::assertSame(
            AliasTarget::class,
            (new NewObjectValidOption())->boundClass()::class,
            '`new` must construct the written class.',
        );

        $this->expectExceptionMessage(
            'Setting unknown property: ' . AliasTarget::class . '::label',
        );

        (new NewObjectUnknownOption())->boundClass();
    }

    public function testRecordEveryPublicFixtureMethod(): void
    {
        foreach (NewObjectRuntimeProvider::fixtures() as $fixture) {
            self::assertEqualsCanonicalizing(
                FixtureMethod::names($fixture),
                NewObjectRuntimeProvider::methods($fixture),
                sprintf('Every public method of `%s` must have a recorded outcome.', $fixture),
            );
        }
    }

    /**
     * @phpstan-param class-string $fixture
     * @phpstan-param class-string $outcome
     * @phpstan-param non-empty-string|null $message
     */
    #[DataProviderExternal(NewObjectRuntimeProvider::class, 'methodProvider')]
    public function testRunConstructionWithRecordedOutcome(
        string $fixture,
        string $method,
        string $outcome,
        string|null $message,
    ): void {
        $callable = [new $fixture(), $method];

        self::assertIsCallable(
            $callable,
            'Fixture method must be callable.',
        );

        try {
            $object = $callable();
        } catch (Throwable $exception) {
            self::assertSame(
                $outcome,
                $exception::class,
                sprintf('Outcome must be `%s`, got: %s', $outcome, $exception->getMessage()),
            );
            self::assertNotNull(
                $message,
                'A recorded failure must name the start of its message.',
            );
            self::assertStringStartsWith(
                $message,
                $exception->getMessage(),
                sprintf('Exception must name the rejected member, got: %s', $exception->getMessage()),
            );

            return;
        }

        self::assertIsObject(
            $object,
            'Fixture method must return the constructed object.',
        );
        self::assertSame(
            $outcome,
            $object::class,
            'Constructed object must have the written class.',
        );
    }
}
