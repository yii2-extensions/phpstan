<?php

declare(strict_types=1);

namespace Yii2\Extensions\PHPStan;

use Closure;
use InvalidArgumentException;
use PhpParser\Node;
use ReflectionException;
use ReflectionFunction;
use ReflectionNamedType;
use RuntimeException;
use yii\base\BaseObject;

use function class_exists;
use function define;
use function defined;
use function get_class;
use function is_array;
use function is_object;
use function is_string;
use function is_subclass_of;
use function sprintf;

final class ServiceMap
{
    /**
     * @var string[]
     */
    private array $services = [];

    /**
     * @var array<string, string>
     */
    private array $components = [];

    /**
     * @throws ReflectionException
     */
    public function __construct(string $configPath)
    {
        if (!file_exists($configPath)) {
            throw new InvalidArgumentException(sprintf('Provided config path %s must exist', $configPath));
        }

        defined('YII_DEBUG') || define('YII_DEBUG', true);
        defined('YII_ENV_DEV') || define('YII_ENV_DEV', false);
        defined('YII_ENV_PROD') || define('YII_ENV_PROD', false);
        defined('YII_ENV_TEST') || define('YII_ENV_TEST', true);

        $config = require $configPath;
        foreach ($config['container']['singletons'] ?? [] as $id => $service) {
            $this->addServiceDefinition($id, $service);
        }

        foreach ($config['container']['definitions'] ?? [] as $id => $service) {
            $this->addServiceDefinition($id, $service);
        }

        foreach ($config['components'] ?? [] as $id => $component) {
            if (is_object($component)) {
                $this->components[$id] = get_class($component);
                continue;
            }

            if (!is_array($component)) {
                throw new RuntimeException(
                    sprintf('Invalid value for component with id %s. Expected object or array.', $id),
                );
            }

            if (null !== $class = $component['class'] ?? null) {
                $this->components[$id] = $class;
            }
        }
    }

    public function getServiceClassFromNode(Node $node): ?string
    {
        if ($node instanceof Node\Scalar\String_ && isset($this->services[$node->value])) {
            return $this->services[$node->value];
        }

        return null;
    }

    public function getComponentClassById(string $id): ?string
    {
        return $this->components[$id] ?? null;
    }

    /**
     * @throws ReflectionException
     *
     * @phpstan-param array<mixed>|string|Closure|int $service
     */
    private function addServiceDefinition(string $id, array|string|Closure|int $service): void
    {
        $this->services[$id] = $this->guessServiceDefinition($id, $service);
    }

    /**
     * @throws ReflectionException
     *
     * @phpstan-param string|Closure|array<mixed>|int $service
     */
    private function guessServiceDefinition(string $id, array|string|Closure|int $service): string
    {
        if (is_string($service) && class_exists($service)) {
            return $service;
        }

        if ($service instanceof Closure || is_string($service)) {
            $returnType = (new ReflectionFunction($service))->getReturnType();
            if (!$returnType instanceof ReflectionNamedType) {
                throw new RuntimeException(sprintf('Please provide return type for %s service closure', $id));
            }

            return $returnType->getName();
        }

        if (!is_array($service)) {
            throw new RuntimeException(sprintf('Unsupported service definition for %s', $id));
        }

        if (isset($service['class'])) {
            return $service['class'];
        }

        if (isset($service[0]['class'])) {
            return $service[0]['class'];
        }

        if (is_subclass_of($id, BaseObject::class)) {
            return $id;
        }

        throw new RuntimeException(sprintf('Cannot guess service definition for %s', $id));
    }
}
