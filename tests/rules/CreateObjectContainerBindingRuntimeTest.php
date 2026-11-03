<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\rules;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use Throwable;
use yii2\extensions\phpstan\tests\data\rule\CreateObjectContainerBinding;
use yii2\extensions\phpstan\tests\provider\CreateObjectContainerBindingRuntimeProvider;
use yii2\extensions\phpstan\tests\support\CreateObjectContainerTrait;

use function is_subclass_of;
use function sprintf;
use function str_starts_with;

/**
 * Runtime tests for the `Yii::createObject()` outcomes of the {@see CreateObjectContainerBinding} fixture.
 *
 * {@see CreateObjectContainerBindingRuntimeProvider} for test case data providers.
 */
final class CreateObjectContainerBindingRuntimeTest extends TestCase
{
    use CreateObjectContainerTrait;

    /**
     * @phpstan-param class-string $outcome
     */
    #[DataProviderExternal(CreateObjectContainerBindingRuntimeProvider::class, 'outcomeProvider')]
    public function testProduceRecordedOutcome(string $method, string $outcome, string $message): void
    {
        $callable = [new CreateObjectContainerBinding(), $method];

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
            self::assertTrue(
                $message !== '' && str_starts_with($exception->getMessage(), $message),
                sprintf('Message must start with `%s`, got: %s', $message, $exception->getMessage()),
            );

            return;
        }

        self::assertFalse(
            is_subclass_of($outcome, Throwable::class),
            sprintf('Creation must throw `%s`.', $outcome),
        );
        self::assertIsObject(
            $object,
            'Fixture method must return the created object.',
        );
        self::assertSame(
            $outcome,
            $object::class,
            'Created object must have the expected class.',
        );
    }
}
