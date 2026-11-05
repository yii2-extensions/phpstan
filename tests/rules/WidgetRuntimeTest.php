<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\rules;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Throwable;
use yii\base\UnknownPropertyException;
use yii2\extensions\phpstan\rules\{WidgetInvalidOptionTypeRule, WidgetUnknownOptionRule};
use yii2\extensions\phpstan\tests\data\rule\{WidgetUnknownOption, WidgetValidOption};
use yii2\extensions\phpstan\tests\provider\WidgetRuntimeProvider;
use yii2\extensions\phpstan\tests\support\{FixtureMethod, WidgetRuntimeTrait};
use yii2\extensions\phpstan\tests\support\widget\{BoundWidget, OtherWidget, TargetWidget};

use function is_object;
use function is_string;
use function ob_get_level;
use function sprintf;

/**
 * Runtime tests for the `widget()` and `begin()` outcomes of the {@see WidgetUnknownOptionRule} and
 * {@see WidgetInvalidOptionTypeRule} fixtures, with the container bindings of the widget analysis configuration.
 *
 * {@see WidgetRuntimeProvider} for test case data providers.
 */
final class WidgetRuntimeTest extends TestCase
{
    use WidgetRuntimeTrait;

    public function testBuildBoundClassAndEndItThroughTheCalledClass(): void
    {
        $widget = BoundWidget::begin(['colour' => 'red']);

        self::assertSame(
            OtherWidget::class,
            $widget::class,
            'Container must build the bound class.',
        );
        self::assertSame(
            'red',
            (new ReflectionProperty(OtherWidget::class, 'colour'))->getValue($widget),
            'Option must be assigned to the bound class.',
        );

        ob_start();

        $ended = BoundWidget::end();

        self::assertSame(
            OtherWidget::class,
            ob_get_clean(),
            'Ended widget must run and echo its class.',
        );
        self::assertSame(
            $widget,
            $ended,
            'Ended widget must be the begun one.',
        );
    }

    public function testCloseOutputBufferWhenAssignmentFails(): void
    {
        $level = ob_get_level();

        try {
            (new WidgetUnknownOption())->unknownOptionWidget();

            self::fail('Assignment must fail.');
        } catch (UnknownPropertyException) {
        }

        self::assertSame(
            $level,
            ob_get_level(),
            'Output buffer of `widget()` must be closed.',
        );
        self::assertSame(
            [],
            self::widgetStack()->getValue(),
            'A failed `widget()` call must leave the stack empty.',
        );
    }

    public function testKeepBegunWidgetOnStackUntilEnd(): void
    {
        $widget = (new WidgetValidOption())->propertiesAndSettersBegin();

        self::assertSame(
            [$widget],
            self::widgetStack()->getValue(),
            'Begun widget must be on the stack.',
        );
        self::assertInstanceOf(
            TargetWidget::class,
            $widget,
            'Begun widget must have the called class.',
        );
        self::assertSame(
            ['value', 1, 'text', 'w0'],
            [$widget->name, $widget->count, $widget->getLabel(), $widget->getId()],
            'Properties and setters must receive their options.',
        );

        ob_start();

        TargetWidget::end();

        ob_end_clean();

        self::assertSame(
            [],
            self::widgetStack()->getValue(),
            'Ended widget must leave the stack.',
        );
    }

    public function testRecordEveryPublicFixtureMethod(): void
    {
        foreach (WidgetRuntimeProvider::fixtures() as $fixture) {
            self::assertEqualsCanonicalizing(
                FixtureMethod::names($fixture),
                WidgetRuntimeProvider::methods($fixture),
                sprintf('Every public method of `%s` must have a recorded outcome.', $fixture),
            );
        }
    }

    /**
     * @phpstan-param class-string $fixture
     * @phpstan-param class-string $outcome
     * @phpstan-param non-empty-string|null $message
     */
    #[DataProviderExternal(WidgetRuntimeProvider::class, 'methodProvider')]
    public function testRunCallWithRecordedOutcome(
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
            $result = $callable();
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

        self::assertNull(
            $message,
            'A recorded success must have no message.',
        );

        // `begin()` returns the created widget; `widget()` returns the output of `run()`, which echoes nothing and
        // returns the class of the created widget
        self::assertSame(
            $outcome,
            match (true) {
                is_object($result) => $result::class,
                is_string($result) => $result,
                default => null,
            },
            'Created widget must have the recorded class.',
        );
    }
}
