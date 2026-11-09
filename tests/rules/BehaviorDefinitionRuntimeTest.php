<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\rules;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use Throwable;
use yii\base\{Component, UnknownMethodException};
use yii2\extensions\phpstan\rules\{AttachBehaviorInvalidDefinitionRule, BehaviorsInvalidDefinitionRule};
use yii2\extensions\phpstan\tests\provider\BehaviorDefinitionRuntimeProvider;
use yii2\extensions\phpstan\tests\support\behavior\{
    AttachableBehavior,
    BehaviorOwner,
    PlainClass,
    ProtectedAttachObject,
    StaticAttachObject,
    StaticAttachOnly,
};
use yii2\extensions\phpstan\tests\support\BehaviorContainerTrait;

use function array_intersect_key;
use function array_keys;
use function is_subclass_of;
use function sprintf;

/**
 * Runtime tests for the attachment outcomes of the {@see BehaviorsInvalidDefinitionRule} and
 * {@see AttachBehaviorInvalidDefinitionRule} fixtures, with the container bindings of their analysis configuration.
 *
 * {@see BehaviorDefinitionRuntimeProvider} for test case data providers.
 */
final class BehaviorDefinitionRuntimeTest extends TestCase
{
    use BehaviorContainerTrait;

    /**
     * @phpstan-param class-string $owner
     * @phpstan-param class-string|null $outcome
     */
    #[DataProviderExternal(BehaviorDefinitionRuntimeProvider::class, 'entryProvider')]
    public function testAttachFixtureEntryWithRecordedOutcome(string $owner, int|string $name, string|null $outcome): void
    {
        $entry = array_intersect_key(self::definitions($owner), [$name => true]);

        self::assertCount(
            1,
            $entry,
            'Fixture must return the recorded entry.',
        );

        $actual = null;
        $receiver = new BehaviorOwner();

        try {
            $receiver->attachBehaviors($entry);
        } catch (Throwable $e) {
            $actual = $e::class;
        }

        self::assertSame(
            $outcome,
            $actual,
            sprintf('Outcome of `%s` must match the recorded one.', $name),
        );
    }

    public function testAttachListWhoseImplicitKeyFollowsNegativeKey(): void
    {
        $owner = new BehaviorOwner();

        $definitions = [-5 => AttachableBehavior::class, PlainClass::class];

        self::assertSame(
            [-5, -4],
            array_keys($definitions),
            'Implicit key must follow the negative key.',
        );

        $definitions[-4] = AttachableBehavior::class;

        $owner->attachBehaviors($definitions);

        self::assertCount(
            2,
            $owner->getBehaviors(),
            'Later `-4` key must replace the implicit one.',
        );

        $this->expectException(UnknownMethodException::class);

        $owner->attachBehaviors(
            [
                -5 => AttachableBehavior::class,
                ProtectedAttachObject::class,
            ],
        );
    }

    public function testAttachStaticAttachThenFailOnlyOnLaterDetach(): void
    {
        $owner = new BehaviorOwner();

        $owner->attachBehavior(
            'static',
            StaticAttachObject::class,
        );
        $owner->attachBehavior(
            'staticOnly',
            StaticAttachOnly::class,
        );

        self::assertInstanceOf(
            StaticAttachObject::class,
            $owner->detachBehavior('static'),
            'Static detach() must detach.',
        );

        $this->expectException(UnknownMethodException::class);

        $owner->detachBehavior('staticOnly');
    }

    public function testRecordEveryEntryOfEachFixture(): void
    {
        foreach (BehaviorDefinitionRuntimeProvider::outcomes() as $owner => $outcomes) {
            self::assertSame(
                array_keys(self::definitions($owner)),
                array_keys($outcomes),
                sprintf('Every entry of `%s` must have a recorded outcome.', $owner),
            );
        }
    }

    /**
     * Returns the `behaviors()` list of a fixture owner.
     *
     * @phpstan-param class-string $owner
     *
     * @return array<array-key, mixed>
     */
    private static function definitions(string $owner): array
    {
        self::assertTrue(
            is_subclass_of($owner, Component::class),
            'Fixture owner must be a component.',
        );

        return (new $owner())->behaviors();
    }
}
