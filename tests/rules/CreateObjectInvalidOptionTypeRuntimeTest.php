<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\rules;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use Throwable;
use TypeError;
use yii2\extensions\phpstan\rules\CreateObjectInvalidOptionTypeRule;
use yii2\extensions\phpstan\tests\provider\CreateObjectInvalidOptionTypeRuntimeProvider;
use yii2\extensions\phpstan\tests\support\CreateObjectContainerTrait;

use function is_callable;
use function is_subclass_of;
use function sprintf;

/**
 * Runtime tests for the `Yii::createObject()` outcomes of the {@see CreateObjectInvalidOptionTypeRule} fixtures.
 *
 * {@see CreateObjectInvalidOptionTypeRuntimeProvider} for test case data providers.
 */
final class CreateObjectInvalidOptionTypeRuntimeTest extends TestCase
{
    use CreateObjectContainerTrait;

    /**
     * @phpstan-param class-string $fixture
     * @phpstan-param class-string $class
     */
    #[DataProviderExternal(CreateObjectInvalidOptionTypeRuntimeProvider::class, 'validProvider')]
    public function testCreateObjectWithAcceptedValues(string $fixture, string $method, string $class): void
    {
        self::assertInstanceOf(
            $class,
            self::call($fixture, $method),
            'Every option must be assigned.',
        );
    }

    /**
     * @phpstan-param class-string $fixture
     * @phpstan-param class-string $outcome
     */
    #[DataProviderExternal(CreateObjectInvalidOptionTypeRuntimeProvider::class, 'standAsideProvider')]
    public function testDocumentOutcomeOfCallLeftAlone(string $fixture, string $method, string $outcome): void
    {
        try {
            $object = self::call($fixture, $method);
        } catch (Throwable $exception) {
            self::assertSame(
                $outcome,
                $exception::class,
                sprintf('Outcome must be `%s`, got: %s', $outcome, $exception->getMessage()),
            );

            return;
        }

        self::assertFalse(
            is_subclass_of($outcome, Throwable::class),
            sprintf('Creation must throw `%s`.', $outcome),
        );
        self::assertInstanceOf(
            $outcome,
            $object,
            'Created object must have the expected class.',
        );
    }

    /**
     * @phpstan-param class-string $fixture
     */
    #[DataProviderExternal(CreateObjectInvalidOptionTypeRuntimeProvider::class, 'reportedProvider')]
    public function testThrowTypeErrorForReportedValue(string $fixture, string $method, string $message): void
    {
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage(
            $message,
        );

        self::call($fixture, $method);
    }

    /**
     * Calls a fixture method, which creates one object through `Yii::createObject()`.
     *
     * @phpstan-param class-string $fixture
     */
    private static function call(string $fixture, string $method): object
    {
        $callable = [new $fixture(), $method];

        self::assertTrue(
            is_callable($callable),
            'Fixture method must be callable.',
        );

        $object = $callable();

        self::assertIsObject(
            $object,
            'Fixture method must return the created object.',
        );

        return $object;
    }
}
