<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\rules;

use Closure;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use Throwable;
use Yii;
use yii\base\UnknownPropertyException;
use yii\di\Container;
use yii2\extensions\phpstan\rules\CreateObjectUnknownOptionRule;
use yii2\extensions\phpstan\tests\provider\CreateObjectUnknownOptionRuntimeProvider;

use function dirname;
use function is_array;
use function is_subclass_of;
use function sprintf;

/**
 * Runtime tests for the `Yii::createObject()` outcomes that {@see CreateObjectUnknownOptionRule} relies on.
 *
 * {@see CreateObjectUnknownOptionRuntimeProvider} for test case data providers.
 */
final class CreateObjectUnknownOptionRuntimeTest extends TestCase
{
    /**
     * Container in place before the test, restored afterwards.
     */
    private Container|null $previousContainer = null;

    /**
     * @phpstan-param Closure(): object $create
     * @phpstan-param class-string $class
     */
    #[DataProviderExternal(CreateObjectUnknownOptionRuntimeProvider::class, 'validOptionProvider')]
    public function testCreateObjectWithValidOptions(Closure $create, string $class): void
    {
        self::assertInstanceOf(
            $class,
            $create(),
            'Every option must be assigned.',
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

    protected function setUp(): void
    {
        $config = require dirname(__DIR__) . '/config/create-object-config.php';
        $container = new Container();

        self::assertTrue(
            is_array($config) && is_array($config['container'] ?? null),
            'Analysis configuration must hold a container section.',
        );

        $definitions = $config['container']['definitions'] ?? [];
        $singletons = $config['container']['singletons'] ?? [];

        self::assertTrue(
            is_array($definitions) && is_array($singletons),
            'Container definitions and singletons must be arrays.',
        );

        $container->setDefinitions($definitions);
        $container->setSingletons($singletons);

        $this->previousContainer = Yii::$container;

        Yii::$container = $container;
    }

    protected function tearDown(): void
    {
        if ($this->previousContainer !== null) {
            Yii::$container = $this->previousContainer;
        }

        $this->previousContainer = null;
    }
}
