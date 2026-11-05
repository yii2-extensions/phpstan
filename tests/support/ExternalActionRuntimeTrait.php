<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support;

use Yii;
use yii\base\{Controller, Module};
use yii\console\{Request, Response};
use yii\di\Container;
use yii2\extensions\phpstan\tests\data\rule\action\AnonymousActionControllerFactory;
use yii2\extensions\phpstan\tests\provider\ExternalActionRuntimeProvider;

use function dirname;
use function is_array;
use function is_subclass_of;

/**
 * Isolates each external action runtime test: replaces `Yii::$container` with the bindings of the external action
 * analysis configuration and restores it afterwards.
 */
trait ExternalActionRuntimeTrait
{
    /**
     * Container in place before the test, restored afterwards.
     */
    private Container|null $previousContainer = null;

    protected function setUp(): void
    {
        $config = require dirname(__DIR__) . '/config/external-action-config.php';

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
     * Returns the fixture controller in a module, with console request and response components.
     *
     * @phpstan-param class-string $fixture
     */
    private static function controller(string $fixture): Controller
    {
        $module = new Module('test');
        $config = ['request' => new Request(), 'response' => new Response()];

        if ($fixture === AnonymousActionControllerFactory::class) {
            return (new AnonymousActionControllerFactory())->create($module, $config);
        }

        self::assertTrue(
            is_subclass_of($fixture, Controller::class),
            'Fixture must be a controller.',
        );

        return new $fixture(ExternalActionRuntimeProvider::CONTROLLER_IDS[$fixture] ?? 'probe', $module, $config);
    }
}
