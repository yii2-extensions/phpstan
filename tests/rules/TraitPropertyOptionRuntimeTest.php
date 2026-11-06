<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\rules;

use PHPUnit\Framework\Attributes\{DataProviderExternal, RequiresPhp};
use PHPUnit\Framework\TestCase;
use Throwable;
use yii\base\{Controller, Module};
use yii\console\{Request, Response};
use yii2\extensions\phpstan\rules\ObjectOptionAssignment;
use yii2\extensions\phpstan\tests\data\rule\traitproperty\{
    TraitPropertyActionController,
    TraitPropertyCreateObject,
    TraitPropertyNewObject,
    TraitPropertyWidget,
};
use yii2\extensions\phpstan\tests\provider\TraitPropertyOptionProvider;
use yii2\extensions\phpstan\tests\support\{FixtureMethod, WidgetRuntimeTrait};

use function array_keys;
use function sprintf;

/**
 * Runtime tests for the Yii outcomes of the trait-redeclared property fixtures that {@see ObjectOptionAssignment}
 * decides for the eight option rules.
 *
 * {@see TraitPropertyOptionProvider} for test case data providers.
 */
#[RequiresPhp('>= 8.5')]
final class TraitPropertyOptionRuntimeTest extends TestCase
{
    use WidgetRuntimeTrait;

    /**
     * Fixture class of each family whose cases are public methods.
     */
    private const array FIXTURES = [
        'createObject' => TraitPropertyCreateObject::class,
        'new' => TraitPropertyNewObject::class,
        'widget' => TraitPropertyWidget::class,
    ];

    /**
     * @phpstan-param array{class-string, string}|null $expected
     */
    #[DataProviderExternal(TraitPropertyOptionProvider::class, 'runtimeProvider')]
    public function testAssignOptionsThroughTheTraitDeclaration(
        string $family,
        string $case,
        array|null $expected,
    ): void {
        $outcome = null;

        try {
            if ($family === 'externalAction') {
                self::controller()->createAction($case);
            } else {
                $callable = [new (self::FIXTURES[$family] ?? TraitPropertyCreateObject::class)(), $case];

                self::assertIsCallable(
                    $callable,
                    'Fixture method must be callable.',
                );

                $callable();
            }
        } catch (Throwable $exception) {
            $outcome = [$exception::class, $exception->getMessage()];
        }

        self::assertSame(
            $expected,
            $outcome,
            'Exception class and message must match, `null` when Yii accepts every option.',
        );
    }

    public function testRecordEveryFixtureCase(): void
    {
        $recorded = [];

        foreach (TraitPropertyOptionProvider::runtimeProvider() as [$family, $case]) {
            $recorded[$family][] = $case;
        }

        foreach (self::FIXTURES as $family => $fixture) {
            self::assertEqualsCanonicalizing(
                FixtureMethod::names($fixture),
                $recorded[$family] ?? [],
                sprintf('Every public method of `%s` must have a recorded outcome.', $fixture),
            );
        }

        self::assertEqualsCanonicalizing(
            array_keys(self::controller()->actions()),
            $recorded['externalAction'] ?? [],
            'Every action ID must have a recorded outcome.',
        );
    }

    /**
     * Returns the fixture controller in a module, with console request and response components.
     */
    private static function controller(): Controller
    {
        return new TraitPropertyActionController(
            'probe',
            new Module('test'),
            ['request' => new Request(), 'response' => new Response()],
        );
    }
}
