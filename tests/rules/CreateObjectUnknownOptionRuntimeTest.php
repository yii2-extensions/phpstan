<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\rules;

use Closure;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use Throwable;
use yii\base\UnknownPropertyException;
use yii2\extensions\phpstan\rules\CreateObjectUnknownOptionRule;
use yii2\extensions\phpstan\tests\provider\CreateObjectUnknownOptionRuntimeProvider;
use yii2\extensions\phpstan\tests\support\CreateObjectContainerTrait;

use function is_subclass_of;
use function sprintf;

/**
 * Runtime tests for the `Yii::createObject()` outcomes that {@see CreateObjectUnknownOptionRule} relies on.
 *
 * {@see CreateObjectUnknownOptionRuntimeProvider} for test case data providers.
 */
final class CreateObjectUnknownOptionRuntimeTest extends TestCase
{
    use CreateObjectContainerTrait;

    /**
     * @phpstan-param Closure(): object $create
     * @phpstan-param class-string $class
     */
    #[DataProviderExternal(CreateObjectUnknownOptionRuntimeProvider::class, 'validOptionProvider')]
    public function testCreateObjectWithValidOptions(Closure $create, string $class): void
    {
        self::assertSame(
            $class,
            $create()::class,
            'Yii must create the class without rejecting any option.',
        );
    }

    /**
     * @phpstan-param Closure(): object $create
     * @phpstan-param class-string $outcome
     */
    #[DataProviderExternal(CreateObjectUnknownOptionRuntimeProvider::class, 'standAsideProvider')]
    public function testDocumentOutcomeOfCallLeftAlone(Closure $create, string $outcome): void
    {
        try {
            $object = $create();
        } catch (Throwable $exception) {
            self::assertInstanceOf(
                $outcome,
                $exception,
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
     * @phpstan-param Closure(): object $create
     */
    #[DataProviderExternal(CreateObjectUnknownOptionRuntimeProvider::class, 'reportedOptionProvider')]
    public function testThrowUnknownPropertyExceptionForReportedOption(
        Closure $create,
        string $class,
        string $option,
    ): void {
        $this->expectException(UnknownPropertyException::class);
        $this->expectExceptionMessage(
            sprintf('Setting unknown property: %s::%s', $class, $option),
        );

        $create();
    }
}
