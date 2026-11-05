<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support;

use ReflectionProperty;
use Yii;
use yii\base\Widget;
use yii\di\Container;

use function dirname;
use function is_array;
use function ob_end_clean;
use function ob_get_level;

/**
 * Isolates each widget runtime test: replaces `Yii::$container` with the bindings of the widget analysis
 * configuration, and restores the container, the widget stack, the resolved classes and the output buffer level
 * afterwards, also when the test fails.
 */
trait WidgetRuntimeTrait
{
    /**
     * Output buffer level before the test.
     */
    private int $previousBufferLevel = 0;
    /**
     * Container in place before the test, restored afterwards.
     */
    private Container|null $previousContainer = null;
    /**
     * Resolved widget classes of `Widget::begin()` before the test.
     *
     * @var array<mixed>
     */
    private array $previousResolvedClasses = [];
    /**
     * Widget stack before the test.
     *
     * @var array<mixed>
     */
    private array $previousStack = [];

    protected function setUp(): void
    {
        $config = require dirname(__DIR__) . '/config/widget-config.php';

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

        $resolvedClasses = self::resolvedClasses()->getValue();
        $stack = self::widgetStack()->getValue();

        $this->previousBufferLevel = ob_get_level();
        $this->previousContainer = Yii::$container;
        $this->previousResolvedClasses = is_array($resolvedClasses) ? $resolvedClasses : [];
        $this->previousStack = is_array($stack) ? $stack : [];

        Yii::$container = $container;
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > $this->previousBufferLevel) {
            ob_end_clean();
        }

        if ($this->previousContainer !== null) {
            Yii::$container = $this->previousContainer;
        }

        self::widgetStack()->setValue(null, $this->previousStack);

        self::resolvedClasses()->setValue(null, $this->previousResolvedClasses);

        $this->previousContainer = null;
    }

    /**
     * Returns the private static map with which `Widget::begin()` records the class it created for the called class.
     */
    private static function resolvedClasses(): ReflectionProperty
    {
        return new ReflectionProperty(Widget::class, '_resolvedClasses');
    }

    /**
     * Returns the widget stack that `Widget::begin()` and `Widget::end()` maintain, which Yii marks as internal.
     */
    private static function widgetStack(): ReflectionProperty
    {
        return new ReflectionProperty(Widget::class, 'stack');
    }
}
