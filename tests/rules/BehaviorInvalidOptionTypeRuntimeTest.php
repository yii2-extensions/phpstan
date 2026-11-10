<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\rules;

use Closure;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use Throwable;
use yii\base\Component;
use yii2\extensions\phpstan\rules\{
    AttachBehaviorInvalidOptionTypeRule,
    BehaviorsInvalidOptionTypeRule,
    CreateObjectBehaviorInvalidOptionTypeRule,
};
use yii2\extensions\phpstan\tests\data\rule\behavior\{AsBehaviorTypes, EnsureOverrideTypeOwner, TypeAttachCalls};
use yii2\extensions\phpstan\tests\provider\BehaviorInvalidOptionTypeRuntimeProvider;
use yii2\extensions\phpstan\tests\support\behavior\{BehaviorOwner, TypedOptionBehavior};
use yii2\extensions\phpstan\tests\support\BehaviorContainerTrait;

use function array_intersect_key;
use function array_keys;
use function sprintf;
use function strpos;
use function substr;

/**
 * Runtime tests for the outcomes of the {@see BehaviorsInvalidOptionTypeRule},
 * {@see AttachBehaviorInvalidOptionTypeRule} and {@see CreateObjectBehaviorInvalidOptionTypeRule} fixtures, with the
 * container bindings of their analysis configuration.
 *
 * {@see BehaviorInvalidOptionTypeRuntimeProvider} for test case data providers.
 */
final class BehaviorInvalidOptionTypeRuntimeTest extends TestCase
{
    use BehaviorContainerTrait;

    /**
     * @phpstan-param class-string<Component> $owner
     */
    #[DataProviderExternal(BehaviorInvalidOptionTypeRuntimeProvider::class, 'entryProvider')]
    public function testAttachFixtureEntryWithRecordedOutcome(string $owner, int|string $name, string $outcome): void
    {
        $entry = array_intersect_key((new $owner())->behaviors(), [$name => true]);

        self::assertCount(
            1,
            $entry,
            'Fixture must return the recorded entry.',
        );

        $receiver = new BehaviorOwner();

        self::assertSame(
            $outcome,
            self::outcome(static function () use ($receiver, $entry): Component {
                $receiver->attachBehaviors($entry);

                return $receiver;
            }, $name),
            sprintf('Outcome of `%s` must match the recorded one.', $name),
        );
    }

    public function testNeverAttachBehaviorsOfOwnerOverridingEnsureBehaviors(): void
    {
        self::assertSame(
            'none',
            self::outcome(static fn(): Component => new EnsureOverrideTypeOwner(), 'rejected'),
            'List must not be attached.',
        );
    }

    public function testRecordEveryCaseOfEachFixture(): void
    {
        foreach (BehaviorInvalidOptionTypeRuntimeProvider::callOutcomes() as $fixture => $methods) {
            foreach ($methods as $method => $outcomes) {
                $cases = $fixture === 'as' ? self::asCases($method) : self::attachCases($method);

                self::assertSame(
                    array_keys($cases),
                    array_keys($outcomes),
                    sprintf('Every case of `%s::%s()` must have a recorded outcome.', $fixture, $method),
                );
            }
        }

        foreach (BehaviorInvalidOptionTypeRuntimeProvider::entryOutcomes() as $owner => $outcomes) {
            self::assertSame(
                array_keys((new $owner())->behaviors()),
                array_keys($outcomes),
                sprintf('Every entry of `%s` must have a recorded outcome.', $owner),
            );
        }
    }

    #[DataProviderExternal(BehaviorInvalidOptionTypeRuntimeProvider::class, 'asCaseProvider')]
    public function testRunAsCaseWithRecordedOutcome(string $method, string $case, string $outcome): void
    {
        $call = self::asCases($method)[$case] ?? null;

        self::assertNotNull(
            $call,
            'Fixture must hold the recorded case.',
        );
        self::assertSame(
            $outcome,
            self::outcome($call, $case),
            sprintf('Outcome of `%s` must match the recorded one.', $case),
        );
    }

    #[DataProviderExternal(BehaviorInvalidOptionTypeRuntimeProvider::class, 'attachCaseProvider')]
    public function testRunAttachCaseWithRecordedOutcome(string $method, string $case, string $outcome): void
    {
        $call = self::attachCases($method)[$case] ?? null;

        self::assertNotNull(
            $call,
            'Fixture must hold the recorded case.',
        );
        self::assertSame(
            $outcome,
            self::outcome(static function () use ($call): Component {
                $owner = new BehaviorOwner();

                $call($owner);

                return $owner;
            }, $case),
            sprintf('Outcome of `%s` must match the recorded one.', $case),
        );
    }

    /**
     * Returns the cases of an `AsBehaviorTypes` method by name.
     *
     * @return array<string, Closure(): object>
     */
    private static function asCases(string $method): array
    {
        $as = new AsBehaviorTypes();

        return match ($method) {
            'reported' => $as->reported(),
            'standAside' => $as->standAside(),
            default => $as->valid(),
        };
    }

    /**
     * Returns the cases of a `TypeAttachCalls` method by name.
     *
     * @return array<string, Closure(BehaviorOwner): void>
     */
    private static function attachCases(string $method): array
    {
        $attach = new TypeAttachCalls();

        return match ($method) {
            'reported' => $attach->reported(),
            'standAside' => $attach->standAside(['class' => TypedOptionBehavior::class, 'count' => 'all']),
            default => $attach->valid(),
        };
    }

    /**
     * Returns the class of the behavior attached under the name, `none`, or the class and message of the exception
     * thrown, without the caller location that PHP appends to argument type errors.
     *
     * @param Closure(): object $run
     */
    private static function outcome(Closure $run, int|string $name): string
    {
        try {
            $owner = $run();
        } catch (Throwable $e) {
            $message = $e->getMessage();

            $location = strpos($message, ', called in ');

            return $e::class . ': ' . ($location === false ? $message : substr($message, 0, $location));
        }

        if ($owner instanceof Component === false) {
            return 'none';
        }

        $behavior = $owner->getBehaviors()[$name] ?? null;

        return $behavior === null ? 'none' : $behavior::class;
    }
}
