<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support;

use Yii;
use yii\di\Container;

use function dirname;
use function is_array;

/**
 * Provides the runtime container of the behavior definition tests, built from the bindings of their analysis
 * configuration and restored after each test.
 */
trait BehaviorContainerTrait
{
    /**
     * Container in place before the test, restored afterwards.
     */
    private Container|null $previousContainer = null;

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
}
