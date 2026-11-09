<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\rules;

use PHPUnit\Framework\Attributes\{DataProviderExternal, RequiresPhp};
use PHPUnit\Framework\TestCase;
use Throwable;
use Yii;
use yii\base\{Controller, Module};
use yii\console\{Request, Response};
use yii\di\Container;
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
use function dirname;
use function is_array;
use function sprintf;

/**
 * Runtime tests for the Yii outcomes of the trait-redeclared property fixtures that {@see ObjectOptionAssignment}
 * decides for the eight option rules, with the container definitions of each family's analysis configuration.
 *
 * {@see TraitPropertyOptionProvider} for test case data providers.
 */
#[RequiresPhp('>= 8.5.0')]
final class TraitPropertyOptionRuntimeTest extends TestCase
{
    use WidgetRuntimeTrait;

    /**
     * Analysis configuration whose container definitions each family uses instead of the widget configuration.
     */
    private const array CONFIGS = [
        'createObject' => 'create-object-config.php',
        'externalAction' => 'external-action-config.php',
    ];
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

        if (isset(self::CONFIGS[$family])) {
            Yii::$container = self::container(self::CONFIGS[$family]);
        }

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
     * Returns a container holding the definitions and singletons of an analysis configuration in `tests/config/`.
     */
    private static function container(string $config): Container
    {
        $config = require dirname(__DIR__) . "/config/{$config}";

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

        return $container;
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
