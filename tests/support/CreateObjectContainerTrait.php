<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support;

use Yii;
use yii\di\Container;

use function dirname;
use function is_array;

/**
 * Replaces `Yii::$container` during each test with a container holding the bindings of the `createObject()` analysis
 * configuration, and restores the previous one afterwards.
 */
trait CreateObjectContainerTrait
{
    /**
     * Container in place before the test, restored afterwards.
     */
    private Container|null $previousContainer = null;

    protected function setUp(): void
    {
        $config = require dirname(__DIR__) . '/config/create-object-config.php';

        $container = new Container();

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
