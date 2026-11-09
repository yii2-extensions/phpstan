<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\rules;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use Throwable;
use yii\base\Component;
use yii2\extensions\phpstan\rules\CreateObjectInvalidAsDefinitionRule;
use yii2\extensions\phpstan\tests\data\rule\behavior\AsOptions;
use yii2\extensions\phpstan\tests\provider\BehaviorAsDefinitionRuntimeProvider;
use yii2\extensions\phpstan\tests\support\BehaviorContainerTrait;

use function array_keys;
use function sort;
use function sprintf;

/**
 * Runtime tests for the outcomes of the {@see CreateObjectInvalidAsDefinitionRule} fixtures, with the container
 * bindings of their analysis configuration.
 *
 * {@see BehaviorAsDefinitionRuntimeProvider} for test case data providers.
 */
final class BehaviorAsDefinitionRuntimeTest extends TestCase
{
    use BehaviorContainerTrait;

    #[DataProviderExternal(BehaviorAsDefinitionRuntimeProvider::class, 'caseProvider')]
    public function testCreateFixtureCaseWithRecordedOutcome(string $method, string $case, string $outcome): void
    {
        $call = self::cases($method)[$case] ?? null;

        self::assertNotNull(
            $call,
            'Fixture must hold the recorded case.',
        );

        try {
            $owner = $call();

            $behavior = $owner instanceof Component ? $owner->getBehavior($case) : null;

            $actual = $behavior === null ? 'none' : $behavior::class;
        } catch (Throwable $e) {
            $actual = $e::class . ': ' . $e->getMessage();
        }

        self::assertSame(
            $outcome,
            $actual,
            sprintf('Outcome of `%s` must match the recorded one.', $case),
        );
    }

    public function testRecordEveryCaseOfEachFixtureMethod(): void
    {
        foreach (BehaviorAsDefinitionRuntimeProvider::outcomes() as $method => $outcomes) {
            $expected = array_keys(self::cases($method));
            $recorded = array_keys($outcomes);

            if ($method === 'standAside') {
                $recorded = [...$recorded, ...BehaviorAsDefinitionRuntimeProvider::notRun()];
            }

            sort($expected);
            sort($recorded);

            self::assertSame(
                $expected,
                $recorded,
                sprintf('Every case of `%s()` must have a recorded outcome or be listed as not run.', $method),
            );
        }
    }

    /**
     * Returns the cases of a fixture method by name.
     *
     * @return array<string, \Closure(): object>
     */
    private static function cases(string $method): array
    {
        $fixture = new AsOptions();

        return match ($method) {
            'reported' => $fixture->reported(),
            'standAside' => $fixture->standAside(),
            default => $fixture->valid(),
        };
    }
}
