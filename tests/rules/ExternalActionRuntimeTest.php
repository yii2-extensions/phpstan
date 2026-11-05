<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\rules;

use PHPUnit\Framework\Attributes\{DataProviderExternal, RequiresPhp};
use PHPUnit\Framework\TestCase;
use Throwable;
use yii2\extensions\phpstan\rules\{ExternalActionInvalidOptionTypeRule, ExternalActionUnknownOptionRule};
use yii2\extensions\phpstan\tests\data\rule\action\ValidActionController;
use yii2\extensions\phpstan\tests\provider\ExternalActionRuntimeProvider;
use yii2\extensions\phpstan\tests\support\action\TargetAction;
use yii2\extensions\phpstan\tests\support\ExternalActionRuntimeTrait;

use function array_keys;
use function array_map;
use function sprintf;
use function str_starts_with;

/**
 * Runtime tests for the `Controller::createAction()` outcomes of the {@see ExternalActionUnknownOptionRule} and
 * {@see ExternalActionInvalidOptionTypeRule} fixtures, with the container bindings of their analysis configuration.
 *
 * {@see ExternalActionRuntimeProvider} for test case data providers.
 */
final class ExternalActionRuntimeTest extends TestCase
{
    use ExternalActionRuntimeTrait;

    #[RequiresPhp('>= 8.4')]
    public function testAssignOptionToBehaviorAttachedByIdOrControllerHook(): void
    {
        $controller = self::controller('yii2\extensions\phpstan\tests\data\rule\action\hook\HookActionController');

        foreach (['hookedId', 'hookedController', 'traitHookedId', 'traitHookedChild'] as $actionId) {
            $action = $controller->createAction($actionId);

            self::assertNotNull(
                $action,
                'Hooked action must be created.',
            );
            self::assertSame(
                'blue',
                $action->__get('colour'),
                sprintf('Behavior of `%s` must receive the option.', $actionId),
            );
        }
    }

    /**
     * @phpstan-param class-string $controller
     * @phpstan-param class-string|null $outcome
     * @phpstan-param non-empty-string|null $message
     */
    #[DataProviderExternal(ExternalActionRuntimeProvider::class, 'actionProvider')]
    public function testCreateActionWithRecordedOutcome(
        string $controller,
        string $actionId,
        string|null $outcome,
        string|null $message,
    ): void {
        $this->assertCreateAction($controller, $actionId, $outcome, $message);
    }

    /**
     * @phpstan-param class-string $controller
     * @phpstan-param class-string $outcome
     * @phpstan-param non-empty-string|null $message
     */
    #[DataProviderExternal(ExternalActionRuntimeProvider::class, 'hookProvider')]
    #[RequiresPhp('>= 8.4')]
    public function testCreateHookedActionWithRecordedOutcome(
        string $controller,
        string $actionId,
        string $outcome,
        string|null $message,
    ): void {
        $this->assertCreateAction($controller, $actionId, $outcome, $message);
    }

    public function testForwardIdControllerAndConfigurationThroughInheritedConstructor(): void
    {
        $controller = self::controller(ValidActionController::class);

        $action = $controller->createAction('properties');

        self::assertInstanceOf(
            TargetAction::class,
            $action,
            'Definition class must be created.',
        );
        self::assertSame(
            ['properties', $controller, 'value', 1],
            [$action->id, $action->controller, $action->name, $action->count],
            'ID, controller and options must be assigned.',
        );
    }

    public function testRecordEveryActionId(): void
    {
        foreach (ExternalActionRuntimeProvider::controllers() as $fixture) {
            $controller = self::controller($fixture);

            self::assertSame(
                array_map(static fn(int|string $id): string => (string) $id, array_keys($controller->actions())),
                ExternalActionRuntimeProvider::actionIds($fixture),
                sprintf('Every action ID of `%s` must have a recorded outcome.', $fixture),
            );
        }
    }

    /**
     * @phpstan-param class-string $controller
     * @phpstan-param class-string|null $outcome
     * @phpstan-param non-empty-string|null $message
     */
    private function assertCreateAction(
        string $controller,
        string $actionId,
        string|null $outcome,
        string|null $message,
    ): void {
        try {
            $action = self::controller($controller)->createAction($actionId);
        } catch (Throwable $exception) {
            self::assertSame(
                [$outcome, true],
                [$exception::class, $message !== null && str_starts_with($exception->getMessage(), $message)],
                sprintf('Exception must match the recorded outcome, got: %s', $exception->getMessage()),
            );

            return;
        }

        self::assertSame(
            [$outcome, null],
            [$action === null ? null : $action::class, $message],
            'Created action must have the recorded class.',
        );
    }
}
