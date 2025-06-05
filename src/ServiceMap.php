<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan;

use Closure;
use PhpParser\Node;
use ReflectionException;
use ReflectionFunction;
use ReflectionNamedType;
use RuntimeException;
use yii\base\{BaseObject, InvalidArgumentException};

use function class_exists;
use function define;
use function defined;
use function file_exists;
use function get_class;
use function is_array;
use function is_object;
use function is_string;
use function is_subclass_of;
use function sprintf;

/**
 * Provides service and component class resolution for Yii application analysis in PHPStan.
 *
 * Integrates Yii's dependency injection and component configuration with PHPStan's static analysis, enabling accurate
 * type inference, autocompletion, and service/component resolution for dynamic application services and components.
 *
 * This class parses the Yii application configuration to extract service and component definitions mapping service IDs
 * and component IDs to their corresponding class names.
 *
 * It supports both singleton and definition-based service registration, as well as component configuration via arrays
 * or instantiated objects.
 *
 * The implementation provides lookup methods for resolving the class name of a service or component by its ID, which
 * are used by PHPStan reflection extensions to enable static analysis and IDE support for dynamic properties and
 * dependency-injected services.
 *
 * Key features.
 * - Handles both array and object component configuration.
 * - Integrates with PHPStan reflection and type extensions for accurate analysis.
 * - Maps service and component IDs to their fully qualified class names.
 * - Parses Yii application config for service and component definitions.
 * - Provides lookup methods for service and component class resolution by ID.
 * - Supports singleton, definition, and closure-based service registration.
 * - Throws descriptive exceptions for invalid or unsupported definitions.
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class ServiceMap
{
    /**
     * Service definitions map for Yii application analysis.
     *
     * @phpstan-var string[]
     */
    private array $services = [];

    /**
     * Component definitions map for Yii application analysis.
     *
     * @phpstan-var array<string, string>
     */
    private array $components = [];

    /**
     * Creates a new instance of the {@see ServiceMap} class.
     *
     * @param string $configPath Path to the Yii application configuration file (default: `''`). If provided, the
     * configuration file must exist and be valid. If empty or not provided, operates with empty service/component maps.
     *
     * @throws InvalidArgumentException If the provided config path doesn't exist.
     * @throws ReflectionException If the service definitions can't be resolved or are invalid.
     * @throws RuntimeException If the provided configuration path doesn't exist or is invalid.
     */
    public function __construct(string $configPath = '')
    {
        if ($configPath !== '' && file_exists($configPath) === false) {
            throw new InvalidArgumentException(sprintf('Provided config path %s must exist', $configPath));
        }

        defined('YII_DEBUG') || define('YII_DEBUG', true);
        defined('YII_ENV_DEV') || define('YII_ENV_DEV', false);
        defined('YII_ENV_PROD') || define('YII_ENV_PROD', false);
        defined('YII_ENV_TEST') || define('YII_ENV_TEST', true);

        $config = $this->loadConfig($configPath);

        $this->addServiceDefinition($config);
        $this->processComponents($config);
        $this->processSingletons($config);
    }

    /**
     * Registers a service definition in the service map for Yii application analysis.
     *
     * Adds a service definition to the internal service map resolving the fully qualified `class` name for the
     * specified service ID.
     *
     * This method supports various service definition formats, including `class` names, `array`, `closure`, and
     * `integer` identifiers, enabling accurate type inference and autocompletion for dependency injected services in
     * PHPStan analysis.
     *
     * The method delegates the resolution of the service class to {@see guessServiceDefinition()} which determines the
     * appropriate `class` name based on the provided service definition.
     *
     * This ensures compatibility with Yii's flexible service registration mechanisms and supports both singleton and
     * definition-based services.
     *
     * @param string $config Service configuration `array` containing the service ID and definition.
     *
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     *
     * @phpstan-param array<array-key, mixed> $config
     */
    private function addServiceDefinition(array $config): void
    {
        $definitions = [];

        if ($config !== [] && isset($config['container']) && is_array($config['container'])) {
            $definitions = $config['container']['definitions'] ?? [];

            if (is_array($definitions) === false) {
                throw new RuntimeException(
                    sprintf('Unsupported service definition for \'%s\'.', gettype($definitions)),
                );
            }

            foreach ($definitions as $id => $service) {
                if (is_string($id) === false) {
                    throw new RuntimeException(sprintf('Service ID must be a string, got \'%s\'.', gettype($id)));
                }

                if (in_array(gettype($service), ['array', 'integer', 'object', 'string'], true) === false) {
                    throw new RuntimeException(
                        sprintf(
                            'Service definition must be an \'array\', \'integer\', \'object\', \'string\', , got ' .
                            '\'%s\'.',
                            gettype($service),
                        ),
                    );
                }

                $this->services[$id] = $this->processDefinition($id, $service);
            }
        }
    }

    /**
     * Retrieves the fully qualified `class` name of a Yii application component by its identifier.
     *
     * Looks up the component `class` name registered under the specified component ID in the internal component map.
     *
     * This method enables static analysis tools and IDEs to resolve the actual `class` type of dynamic application
     * components for accurate type inference, autocompletion, and property reflection.
     *
     * @param string $id Component identifier to look up in the component map.
     *
     * @return string|null Fully qualified `class` name of the component, or `null` if not found.
     */
    public function getComponentClassById(string $id): string|null
    {
        return $this->components[$id] ?? null;
    }

    /**
     * Resolves the fully qualified `class` name of a service from a PHP-Parser AST node.
     *
     * Inspects the provided AST node to determine if it represents a string service identifier, and if so, look up
     * the corresponding `class` name in the internal service map.
     *
     * This method enables static analysis tools and IDEs to infer the actual `class` type of services referenced by
     * string IDs in Yii application code supporting accurate type inference, autocompletion, and dependency injection
     * analysis.
     *
     * @param Node $node PHP-Parser AST node representing a service identifier.
     *
     * @return string|null Fully qualified `class` name of the service, or `null` if not found.
     */
    public function getServiceClassFromNode(Node $node): string|null
    {
        if ($node instanceof Node\Scalar\String_ && isset($this->services[$node->value])) {
            return $this->services[$node->value];
        }

        return null;
    }

    /**
     * Loads and validates the Yii application configuration file.
     *
     * Reads the specified configuration file and ensures it returns an `array` structure suitable for service and
     * component registration.
     *
     * This method is essential for initializing the service and component maps used in PHPStan analysis, supporting
     * accurate type inference and autocompletion for Yii application dependencies.
     *
     * @param string $configPath Path to the Yii application configuration file. If empty, returns an empty `array`.
     *
     * @throws RuntimeException if the configuration file does not return an `array`.
     *
     * @phpstan-return array<array-key, array<array-key, mixed>|mixed>
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

        return $config;
    }

    /**
     * Processes and registers Yii application components for static analysis.
     *
     * Iterates over the `components` section of the Yii application configuration `array`, extracting and registering
     * component `class` definitions by their identifiers.
     *
     * This enables accurate type inference, autocompletion, and  property reflection for dynamic application components
     * in PHPStan analysis.
     *
     * @param array $config Yii application configuration array containing the `components` section.
     *
     * @throws RuntimeException if a component ID is not a `string` or if the component definition is not an `object` or
     * `array`.
     *
     * @phpstan-param array<array-key, mixed> $config
     */
    private function processComponents(array $config): void
    {
        if ($config !== [] && isset($config['components']) && is_array($config['components'])) {
            foreach ($config['components'] as $id => $component) {
                if (is_string($id) === false) {
                    throw new RuntimeException(sprintf('Component ID must be a string, got \'%s\'.', gettype($id)));
                }

                if (is_object($component)) {
                    $this->components[$id] = get_class($component);

                    continue;
                }

                if (is_array($component) === false) {
                    throw new RuntimeException(
                        sprintf('Unsupported component definition for \'%s\'.', gettype($component)),
                    );
                }

                if (isset($component['class']) && is_string($component['class']) && $component['class'] !== '') {
                    $this->components[$id] = $component['class'];
                }
            }
        }
    }

    /**
     * Infers the fully qualified `class` name for a Yii service definition.
     *
     * Resolves the `class` name associated with a service definition provided in supported formats, including `class`
     * name `string`, configuration `array`, `closure`, or `integer` identifiers.
     *
     * This method enables static analysis tools and IDEs to determine the actual class type of dependency injected
     * services for accurate type inference, autocompletion, and service resolution in PHPStan analysis.
     *
     * The implementation inspects the service definition to extract the `class` name, supporting Yii's flexible service
     * registration mechanisms and ensuring compatibility with both singleton and definition-based services.
     *
     * @param string $id Service identifier being resolved.
     * @param array|int|object|string $service Service definition in supported format.
     *
     * @throws ReflectionException if the service definition is invalid or cannot be resolved.
     * @throws RuntimeException if the service definition format is unsupported or missing required information.
     *
     * @return string Fully qualified `class` name of the resolved service.
     *
     * @phpstan-param array<mixed>|int|object|string $service
     */
    private function processDefinition(string $id, array|int|object|string $service): string
    {
        if (is_string($service) && class_exists($service)) {
            return $service;
        }

        if ($service instanceof Closure || is_string($service)) {
            $returnType = (new ReflectionFunction($service))->getReturnType();

            if ($returnType instanceof ReflectionNamedType === false) {
                throw new RuntimeException(sprintf('Please provide return type for \'%s\' service closure.', $id));
            }

            return $returnType->getName();
        }

        if (is_array($service) === false) {
            throw new RuntimeException(sprintf('Unsupported service definition for \'%s\'.', $id));
        }

        if (isset($service['class']) && is_string($service['class']) && $service['class'] !== '') {
            return $service['class'];
        }

        if (isset($service[0]['class']) && is_string($service[0]['class']) && $service[0]['class'] !== '') {
            return $service[0]['class'];
        }

        if (is_subclass_of($id, BaseObject::class)) {
            return $id;
        }

        throw new RuntimeException(sprintf('Cannot guess service definition for \'%s\'.', $id));
    }

    /**
     * Processes and registers singleton service definitions from the Yii application configuration.
     *
     * Integrates singleton service definitions declared in the Yii application's `container` configuration with the
     * internal service map, enabling accurate type inference and autocompletion for dependency-injected singletons in
     * PHPStan analysis.
     *
     * This method validates the structure and types of singleton definitions, ensuring that only supported formats
     * (`arrays`, `objects`, or `class` name `string`) are processed.
     *
     * It throws descriptive exceptions for unsupported or invalid singleton definitions, maintaining strict
     * compatibility with Yii's dependency injection container.
     *
     * The implementation iterates over each singleton entry, validates the identifier and service definition, and
     * delegates `class` name resolution to {@see processDefinition()} for accurate type mapping.
     *
     * @param array $config Yii application configuration `array` containing singleton definitions.
     *
     * @throws RuntimeException if the singleton definitions are invalid or unsupported.
     *
     * @phpstan-param array<array-key, mixed> $config
     */
    private function processSingletons(array $config): void
    {
        $singletons = [];

        if ($config !== [] && isset($config['container']) && is_array($config['container'])) {
            $singletons = $config['container']['singletons'] ?? [];

            if (is_array($singletons) === false) {
                throw new RuntimeException(
                    sprintf('Unsupported singletons definition for \'%s\'.', gettype($singletons)),
                );
            }

            foreach ($singletons as $id => $service) {
                if (is_string($id) === false) {
                    throw new RuntimeException(sprintf('Singleton ID must be a string, got \'%s\'.', gettype($id)));
                }

                if (is_array($service) === false && is_object($service) === false && is_string($service) === false) {
                    throw new RuntimeException(
                        sprintf('Singleton service definition must be an array, got \'%s\'.', gettype($service)),
                    );
                }

                $this->services[$id] = $this->processDefinition($id, $service);
            }
        }
    }
}
