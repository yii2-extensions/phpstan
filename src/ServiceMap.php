<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan;

use Closure;
use ReflectionException;
use ReflectionFunction;
use ReflectionNamedType;
use RuntimeException;
use yii\base\{BaseObject, InvalidArgumentException};
use yii\web\Application;

use function array_key_exists;
use function define;
use function defined;
use function file_exists;
use function is_file;
use function gettype;
use function is_array;
use function is_object;
use function is_readable;
use function is_string;
use function is_subclass_of;
use function pathinfo;
use function sprintf;
use const PATHINFO_EXTENSION;

/**
 * Maps and normalizes service and component definitions from Yii Application configuration for static analysis.
 *
 * Loads, validates, and processes configuration files, exposing lookup methods to resolve class names and configuration
 * arrays by identifier or class name. Supports direct class names, closures, arrays, and object instances.
 */
final class ServiceMap
{
    /**
     * Application type for PHPStan analysis.
     *
     * @phpstan-var class-string<\yii\base\Application>|string
     */
    private string $applicationType = '';

    /**
     * Behavior definitions map for Yii Application analysis.
     *
     * @phpstan-var array<string, string[]>
     */
    private array $behaviors = [];

    /**
     * Reverse index mapping class names to component IDs for optimized lookups.
     *
     * @phpstan-var array<string, string>
     */
    private array $componentClassToIdMap = [];

    /**
     * Component definitions map for Yii Application analysis.
     *
     * @phpstan-var string[]
     */
    private array $components = [];

    /**
     * Component definitions for Yii Application analysis.
     *
     * @phpstan-var array<string, mixed>
     */
    private array $componentsDefinitions = [];

    /**
     * Application params for PHPStan type inference.
     *
     * @phpstan-var array<array-key, mixed>
     */
    private array $params = [];

    /**
     * Service definitions map for Yii Application analysis.
     *
     * @phpstan-var class-string[]|string[]
     */
    private array $services = [];

    /**
     * Creates a new instance of the {@see ServiceMap} class.
     *
     * @param string $configPath Path to the Yii Application configuration file (default: `''`). If provided, the
     * configuration file must exist and be valid. If empty or not provided, operates with empty service/component maps.
     *
     * @throws InvalidArgumentException if one or more arguments are invalid, of incorrect type or format.
     * @throws ReflectionException if the service definitions can't be resolved or are invalid.
     * @throws RuntimeException if a runtime error prevents the operation from completing successfully.
     */
    public function __construct(string $configPath = '')
    {
        if (
            $configPath !== ''
            && (
                file_exists($configPath) === false
                || is_file($configPath) === false
                || is_readable($configPath) === false
                || pathinfo($configPath, PATHINFO_EXTENSION) !== 'php'
            )
        ) {
            throw new InvalidArgumentException(
                sprintf('Provided config path \'%s\' must be a readable PHP file.', $configPath),
            );
        }

        defined('YII_DEBUG') || define('YII_DEBUG', true);
        defined('YII_ENV_DEV') || define('YII_ENV_DEV', false);
        defined('YII_ENV_PROD') || define('YII_ENV_PROD', false);
        defined('YII_ENV_TEST') || define('YII_ENV_TEST', true);

        $config = $this->loadConfig($configPath);

        $this->processApplicationType($config);
        $this->processBehaviors($config);
        $this->processComponents($config);
        $this->processDefinition($config);
        $this->processParams($config);
        $this->processSingletons($config);
    }

    /**
     * Retrieves the fully qualified class name of the application type for PHPStan analysis.
     *
     * @return string Fully qualified class name of the application type.
     *
     * @phpstan-return class-string|string
     */
    public function getApplicationType(): string
    {
        return $this->applicationType;
    }

    /**
     * Retrieves the behavior class names associated with the specified class.
     *
     * Looks up the internal behavior definitions map for the provided fully qualified class name and return an array
     * of associated behavior class names.
     *
     * @param string $class Fully qualified class name for which to retrieve behavior class names.
     *
     * @return string[] Array of behavior class names, or an empty array if none are defined.
     *
     * @phpstan-return string[]
     */
    public function getBehaviorsByClassName(string $class): array
    {
        return $this->behaviors[$class] ?? [];
    }

    /**
     * Retrieves the fully qualified class name of a Yii Application component by its identifier.
     *
     * Looks up the component class name registered under the specified component ID in the internal component map.
     *
     * @param string $id Component identifier to look up in the component map.
     *
     * @return string|null Fully qualified class name of the component, or `null` if not found.
     */
    public function getComponentClassById(string $id): string|null
    {
        return $this->components[$id] ?? null;
    }

    /**
     * Retrieves the component definition array for a given class name.
     *
     * Searches the internal component map for a component whose class name matches the provided fully qualified class
     * name.
     *
     * @param string $class Fully qualified class name to look up in the component map.
     *
     * @return array|null Component definition array with configuration options, or `null` if not found or not an array.
     *
     * @phpstan-return array<array-key, mixed>|null
     */
    public function getComponentDefinitionByClassName(string $class): array|null
    {
        $id = $this->componentClassToIdMap[$class] ?? null;

        if ($id === null) {
            return null;
        }

        return $this->getComponentDefinitionById($id);
    }

    /**
     * Retrieves the component definition array by its identifier.
     *
     * Looks up the component definition registered under the specified component ID in the internal component
     * definitions map.
     *
     * @param string $id Component identifier to look up in the component definitions map.
     *
     * @return array Component definition array with configuration options, or empty array if not found.
     *
     * @phpstan-return array<array-key, mixed>
     */
    public function getComponentDefinitionById(string $id): array
    {
        $definition = $this->componentsDefinitions[$id] ?? null;

        return is_array($definition) ? $definition : [];
    }

    /**
     * Retrieves the application params map for PHPStan type inference.
     *
     * Returns the `params` key-value pairs extracted from the Yii Application configuration file, enabling static
     * analysis tools to infer precise array shape types for `Yii::$app->params` access.
     *
     * @return array<array-key, mixed> Params key-value pairs from configuration.
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Retrieves the fully qualified class name of a Yii Service by its identifier.
     *
     * Looks up the service class name registered under the specified service ID in the internal service map.
     *
     * @param string $id Service identifier to look up in the service map.
     *
     * @return string|null Fully qualified class name of the service, or `null` if not found.
     *
     * @phpstan-return class-string|string|null
     */
    public function getServiceById(string $id): string|null
    {
        return $this->services[$id] ?? null;
    }

    /**
     * Loads and validates the Yii Application configuration file for static analysis.
     *
     * Ensures the specified configuration file returns a valid array structure and that all required sections including
     * `phpstan`, `behaviors`, `components`, `container`, `container.definitions`, and `container.singletons` are arrays
     * when present.
     *
     * @param string $configPath Path to the Yii Application configuration file. If empty, return an empty array.
     *
     * @throws RuntimeException if a runtime error prevents the operation from completing successfully.
     *
     * @return array Normalized configuration array for further processing, or an empty array if no config is provided.
     *
     * @phpstan-return array<array-key, mixed>
     */
    private function loadConfig(string $configPath): array
    {
        if ($configPath === '') {
            return [];
        }

        $config = require $configPath;

        if (is_array($config) === false) {
            throw new RuntimeException(sprintf("Configuration file '%s' must return an array.", $configPath));
        }

        if (isset($config['phpstan'])) {
            if (is_array($config['phpstan']) === false) {
                $this->throwErrorWhenConfigFileIsNotArray($configPath, 'phpstan');
            }

            if (
                isset($config['phpstan']['application_type'])
                && is_string($config['phpstan']['application_type']) === false
            ) {
                $applicationType = gettype($config['phpstan']['application_type']);

                $this->throwErrorWhenIsNotString('Application type', 'phpstan.application_type', $applicationType);
            }
        }

        if (isset($config['behaviors']) && is_array($config['behaviors']) === false) {
            $this->throwErrorWhenConfigFileIsNotArray($configPath, 'behaviors');
        }

        if (isset($config['components']) && is_array($config['components']) === false) {
            $this->throwErrorWhenConfigFileIsNotArray($configPath, 'components');
        }

        if (array_key_exists('params', $config) && is_array($config['params']) === false) {
            $this->throwErrorWhenConfigFileIsNotArray($configPath, 'params');
        }

        if (isset($config['container'])) {
            if (is_array($config['container']) === false) {
                $this->throwErrorWhenConfigFileIsNotArray($configPath, 'container');
            }

            if (isset($config['container']['definitions']) && is_array($config['container']['definitions']) === false) {
                $this->throwErrorWhenConfigFileIsNotArray($configPath, 'container.definitions');
            }

            if (isset($config['container']['singletons']) && is_array($config['container']['singletons']) === false) {
                $this->throwErrorWhenConfigFileIsNotArray($configPath, 'container.singletons');
            }
        }

        return $config;
    }

    /**
     * Resolves a service definition to its fully qualified class name for Yii static analysis.
     *
     * Supports multiple Yii configuration patterns, including direct class names, closures with return types,
     * configuration arrays, and object instances.
     *
     * @param string $id Identifier of the service being normalized.
     * @param mixed $definition Service definition to normalize (class name, closure, array, or object).
     *
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     * @throws RuntimeException if a runtime error prevents the operation from completing successfully.
     *
     * @return string Fully qualified class name resolved from the definition.
     *
     * @phpstan-return class-string|string
     */
    private function normalizeDefinition(string $id, mixed $definition): string
    {
        if (is_string($definition)) {
            return $definition;
        }

        if (is_object($definition) && $definition::class === Closure::class) {
            $returnType = (new ReflectionFunction($definition))->getReturnType();

            if ($returnType === null || $returnType::class !== ReflectionNamedType::class) {
                throw new RuntimeException(sprintf('Please provide return type for \'%s\' service closure.', $id));
            }

            return $returnType->getName();
        }

        if (is_array($definition)) {
            $nested = $definition[0] ?? null;
            $class = $definition['class'] ?? (is_array($nested) ? ($nested['class'] ?? null) : null);

            if (is_string($class) && $class !== '') {
                return $class;
            }
        }

        if (is_subclass_of($id, BaseObject::class)) {
            return $id;
        }

        $this->throwErrorWhenUnsupportedDefinition($id);
    }

    /**
     * Sets the application type for PHPStan analysis from the configuration array.
     *
     * Extracts the application type from the `phpstan.application_type` key in the provided configuration array.
     *
     * If not set, defaults to {@see Application::class}.
     *
     * @param array $config Yii Application configuration array containing PHPStan settings.
     *
     * @phpstan-param array<array-key, mixed> $config
     */
    private function processApplicationType(array $config): void
    {
        $phpstan = $config['phpstan'] ?? null;
        $applicationType = is_array($phpstan) ? ($phpstan['application_type'] ?? null) : null;

        $this->applicationType = is_string($applicationType) && $applicationType !== ''
            ? $applicationType
            : Application::class;
    }

    /**
     * Processes and registers behavior definitions from the Yii Application configuration array.
     *
     * Iterates over the `behaviors` section of the provided configuration array, validating each behavior ID and
     * definition.
     *
     * For each valid behavior, stores an array of associated behavior class names indexed by the behavior ID.
     *
     * This enables static analysis tools and IDEs to resolve attached behaviors for Yii Application classes, supporting
     * accurate type inference and property reflection.
     *
     * @param array $config Yii Application configuration array containing behavior definitions.
     *
     * @throws RuntimeException if a behavior ID is not a string, or if a behavior definition is not an array.
     *
     * @phpstan-param array<array-key, mixed> $config
     */
    private function processBehaviors(array $config): void
    {
        if ($config !== []) {
            $behaviors = $config['behaviors'] ?? [];
            $behaviors = is_array($behaviors) ? $behaviors : [];

            foreach ($behaviors as $id => $definition) {
                if (is_string($id) === false) {
                    $this->throwErrorWhenIsNotString('Behavior class', 'ID', gettype($id));
                }

                if (is_array($definition) === false) {
                    throw new RuntimeException(
                        sprintf("Behavior definition for '%s' must be an array.", $id),
                    );
                }

                $this->behaviors[$id] = array_values(array_filter($definition, is_string(...)));
            }
        }
    }

    /**
     * Processes component definitions from the Yii Application configuration array.
     *
     * Iterates over the components section of the provided configuration array, normalizing and registering each
     * component definition by its identifier.
     *
     * @param array $config Yii Application configuration array containing component definitions.
     *
     * @throws RuntimeException if a runtime error prevents the operation from completing successfully.
     *
     * @phpstan-param array<array-key, mixed> $config
     */
    private function processComponents(array $config): void
    {
        if ($config !== []) {
            $components = $config['components'] ?? [];
            $components = is_array($components) ? $components : [];

            foreach ($components as $id => $definition) {
                if (is_string($id) === false) {
                    $this->throwErrorWhenIsNotString('Component', 'ID', gettype($id));
                }

                if (is_object($definition)) {
                    $className = $definition::class;

                    $this->components[$id] = $className;
                    $this->componentClassToIdMap[$className] = $id;

                    continue;
                }

                if (
                    is_array($definition)
                    && isset($definition['class'])
                    && is_string($definition['class'])
                    && $definition['class'] !== ''
                ) {
                    $className = $definition['class'];

                    $this->components[$id] = $className;
                    $this->componentClassToIdMap[$className] = $id;

                    unset($definition['class']);

                    $this->componentsDefinitions[$id] = $definition;
                }
            }
        }
    }

    /**
     * Processes service definitions from the Yii Application configuration array.
     *
     * Iterates over the container.definitions section of the provided configuration array, normalizing and registering
     * each service definition by its identifier.
     *
     * @param array $config Yii Application configuration array containing service definitions.
     *
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     * @throws RuntimeException if a runtime error prevents the operation from completing successfully.
     *
     * @phpstan-param array<array-key, mixed> $config
     */
    private function processDefinition(array $config): void
    {
        if ($config !== []) {
            $container = $config['container'] ?? null;
            $container = is_array($container) ? $container : [];
            $definitions = $container['definitions'] ?? [];
            $definitions = is_array($definitions) ? $definitions : [];

            foreach ($definitions as $id => $service) {
                if (is_string($id) === false) {
                    $this->throwErrorWhenIsNotString('Definition', 'ID', gettype($id));
                }

                $this->services[$id] = $this->normalizeDefinition($id, $service);
            }
        }
    }

    /**
     * Processes application params from the Yii Application configuration array.
     *
     * Extracts the `params` section and stores it for type inference of `Yii::$app->params` array access.
     *
     * @param array $config Yii Application configuration array containing params definitions.
     *
     * @phpstan-param array<array-key, mixed> $config
     */
    private function processParams(array $config): void
    {
        if ($config !== []) {
            $params = $config['params'] ?? [];
            $this->params = is_array($params) ? $params : [];
        }
    }

    /**
     * Processes singleton service definitions from the Yii Application configuration array.
     *
     * Iterates over the container.singletons section of the provided configuration array, normalizing and registering
     * each singleton service definition by its identifier.
     *
     * @param array $config Yii Application configuration array containing singleton definitions.
     *
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     * @throws RuntimeException if a runtime error prevents the operation from completing successfully.
     *
     * @phpstan-param array<array-key, mixed> $config
     */
    private function processSingletons(array $config): void
    {
        if ($config !== []) {
            $container = $config['container'] ?? null;
            $container = is_array($container) ? $container : [];
            $singletons = $container['singletons'] ?? [];
            $singletons = is_array($singletons) ? $singletons : [];

            foreach ($singletons as $id => $service) {
                if (is_string($id) === false) {
                    $this->throwErrorWhenIsNotString('Singleton', 'ID', gettype($id));
                }

                $this->services[$id] = $this->normalizeDefinition($id, $service);
            }
        }
    }

    /**
     * Throws a {@see RuntimeException} when a configuration file section is not an array.
     *
     * It ensures that only valid array structures are processed during configuration parsing, providing a clear and
     * descriptive error message for debugging and static analysis.
     *
     * @param string ...$args Arguments describing the configuration file path and the invalid section name.
     *
     * @throws RuntimeException if a runtime error prevents the operation from completing successfully.
     */
    private function throwErrorWhenConfigFileIsNotArray(string ...$args): never
    {
        throw new RuntimeException(
            sprintf("Configuration file '%s' must contain a valid '%s' 'array'.", ...$args),
        );
    }

    /**
     * Throws a {@see RuntimeException} when a service or component ID is not a string.
     *
     * It ensures that only valid string identifiers are processed during service and component mapping, providing a
     * clear and descriptive error message for debugging and static analysis.
     *
     * @param string ...$args Arguments describing the context and the invalid identifier type.
     *
     * @throws RuntimeException if a runtime error prevents the operation from completing successfully.
     */
    private function throwErrorWhenIsNotString(string ...$args): never
    {
        throw new RuntimeException(
            sprintf("'%s': '%s' must be a 'string', got '%s'.", ...$args),
        );
    }

    /**
     * Throws a {@see RuntimeException} when a service or component definition is unsupported.
     *
     * It ensures that only valid and supported definitions are processed during service and component resolution,
     * providing a clear and descriptive error message for debugging and static analysis.
     *
     * @param string $id Identifier of the service or component with the unsupported definition.
     *
     * @throws RuntimeException if a runtime error prevents the operation from completing successfully.
     */
    private function throwErrorWhenUnsupportedDefinition(string $id): never
    {
        throw new RuntimeException(
            sprintf("Unsupported definition for '%s'.", $id),
        );
    }
}
