<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\rules;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use Throwable;
use Yii;
use yii\base\{Component, UnknownMethodException};
use yii\di\Container;
use yii2\extensions\phpstan\rules\{AttachBehaviorInvalidDefinitionRule, BehaviorsInvalidDefinitionRule};
use yii2\extensions\phpstan\tests\provider\BehaviorDefinitionRuntimeProvider;
use yii2\extensions\phpstan\tests\support\behavior\{BehaviorOwner, StaticAttachObject, StaticAttachOnly};

use function array_intersect_key;
use function array_keys;
use function dirname;
use function is_array;
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
    /**
     * Container in place before the test, restored afterwards.
     */
    private Container|null $previousContainer = null;

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

    public function testAttachStaticAttachThenFailOnlyOnLaterDetach(): void
    {
        $owner = new BehaviorOwner();

        $owner->attachBehavior(
            'static',
            StaticAttachObject::class,
        );
        $owner->attachBehavior(
            'staticOnly',
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

    protected function setUp(): void
    {
        $config = require dirname(__DIR__) . '/config/behavior-definition-config.php';

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

        $container = new Container();

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
