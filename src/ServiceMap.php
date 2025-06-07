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
use yii\web\User;

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
 * @phpstan-type DefinitionType = array{class?: mixed}|array{array{class?: mixed}}|object|string
 * @phpstan-type ServiceType = array{
 *   components?: array<array-key, array<array-key, mixed>|object>,
 *   container?: array{
 *     definitions?: array<array-key, DefinitionType>,
 *     singletons?: array<array-key, DefinitionType>,
 *   }
 * }
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
     * @phpstan-var string[]
     */
    private array $components = [];

    /**
     * @phpstan-var array<string, string>
     */
    private array $userComponents = [];

    /**
     * Creates a new instance of the {@see ServiceMap} class.
     *
     * @param string $configPath Path to the Yii application configuration file (default: `''`). If provided, the
     * configuration file must exist and be valid. If empty or not provided, operates with empty service/component maps.
     *
     * @throws InvalidArgumentException if one or more arguments are invalid, of incorrect type or format.
     * @throws ReflectionException if the service definitions can't be resolved or are invalid.
     * @throws RuntimeException if a runtime error prevents the operation from completing successfully.
     */
    public function __construct(string $configPath = '')
    {
        if ($configPath !== '' && (file_exists($configPath) === false || is_readable($configPath) === false)) {
            throw new InvalidArgumentException(
                sprintf('Provided config path \'%s\' must be a readable file.', $configPath),
            );
        }

        defined('YII_DEBUG') || define('YII_DEBUG', true);
        defined('YII_ENV_DEV') || define('YII_ENV_DEV', false);
        defined('YII_ENV_PROD') || define('YII_ENV_PROD', false);
        defined('YII_ENV_TEST') || define('YII_ENV_TEST', true);

        $config = $this->loadConfig($configPath);

        $this->processComponents($config);
        $this->processDefinition($config);
        $this->processSingletons($config);
    }

    /**
     * Retrieves the fully qualified class name of a Yii application component by its identifier.
     *
     * Looks up the component class name registered under the specified component ID in the internal component map.
     *
     * This method enables static analysis tools and IDEs to resolve the actual class type of dynamic application
     * components for accurate type inference, autocompletion, and property reflection.
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
     * Retrieves the fully qualified identityClass name of a Yii application user-component by its identifier.
     *
     * Looks up the user-component class name registered under the specified component ID in the internal component map.
     *
     * This method enables static analysis tools and IDEs to resolve the actual class type of dynamic application
     * user-components for accurate type inference, autocompletion, and property reflection.
     *
     * @param string $id Component identifier to look up in the component map.
     *
     * @return string|null Fully qualified class name of the component, or `null` if not found.
     */
    public function getUserComponentClassById(string $id): string|null
    {
        return $this->userComponents[$id] ?? null;
    }

    /**
     * Resolves the fully qualified class name of a service from a PHP-Parser AST node.
     *
     * Inspects the provided AST node to determine if it represents a string service identifier, and if so, look up
     * the corresponding class name in the internal service map.
     *
     * This method enables static analysis tools and IDEs to infer the actual class type of services referenced by
     * string IDs in Yii application code supporting accurate type inference, autocompletion, and dependency injection
     * analysis.
     *
     * @param Node $node PHP-Parser AST node representing a service identifier.
     *
     * @return string|null Fully qualified class name of the service, or `null` if not found.
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
     * Reads the specified configuration file, ensuring it returns a valid array structure and that all required
     * sections (such as components, container, container.definitions, and container.singletons) are arrays.
     *
     * This method is responsible for parsing the Yii application configuration, providing a normalized array for
     * further processing by the service and component mapping logic. It throws descriptive exceptions if the file is
     * missing, does not return an array, or contains invalid section types, ensuring robust error handling and
     * predictable static analysis.
     *
     * @param string $configPath Path to the Yii application configuration file. If empty, returns an empty array.
     *
     * @throws RuntimeException if the closure does not have a return type or the definition is unsupported.
     *
     * @phpstan import-type ServiceType from ServiceMap
     * @phpstan-return array{}|ServiceType Normalized configuration array or empty array if no config is provided.
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

        if (isset($config['components']) && is_array($config['components']) === false) {
            $this->throwErrorWhenConfigFileIsNotArray($configPath, 'components');
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
     * Normalizes a service definition to its fully qualified class name.
     *
     * Resolves the provided service definition to a class name string, supporting various Yii configuration patterns
     * including direct class names, closures, arrays, and object instances.
     *
     * This method enables static analysis tools and IDEs to infer the actual class type of services defined in the Yii
     * application configuration, supporting accurate type inference and autocompletion.
     *
     * @param string $id Service identifier being normalized.
     * @param array|int|object|string $definition Service definition to normalize.
     *
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     * @throws RuntimeException if the closure does not have a return type or the definition is unsupported.
     *
     * @phpstan-import-type DefinitionType from ServiceMap
     * @phpstan-param DefinitionType $definition
     *
     * @return string Fully qualified class name resolved from the definition.
     */
    private function normalizeDefinition(string $id, array|int|object|string $definition): string
    {
        if (is_string($definition) && class_exists($definition)) {
            return $definition;
        }

        if ($definition instanceof Closure || (is_string($definition) && is_callable($definition))) {
            $returnType = (new ReflectionFunction($definition))->getReturnType();

            if ($returnType instanceof ReflectionNamedType === false) {
                throw new RuntimeException(sprintf('Please provide return type for \'%s\' service closure.', $id));
            }

            return $returnType->getName();
        }

        if (is_array($definition)) {
            $class = $definition['class'] ?? ($definition[0]['class'] ?? null);

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
     * Processes component definitions and user-component definitions from the Yii application configuration array.
     *
     * Iterates over the components section of the provided configuration array, normalizing and registering each
     * component definition / user-component identity class definition by its identifier.
     *
     * This method ensures that all components and user-components are mapped to their fully qualified class names
     * and fully qualified identityClass names respectively for accurate static analysis and type inference,
     * supporting IDE autocompletion and property reflection for dynamic application components.
     *
     * @param array $config Yii application configuration array containing component definitions.
     *
     * @throws RuntimeException if a runtime error prevents the operation from completing successfully.
     *
     * @phpstan-import-type ServiceType from ServiceMap
     * @phpstan-param ServiceType $config
     */
    private function processComponents(array $config): void
    {
        if ($config !== []) {
            $components = $config['components'] ?? [];

            foreach ($components as $id => $definition) {
                if (is_string($id) === false) {
                    $this->throwErrorWhenIdIsNotString('Component', gettype($id));
                }

                if (is_object($definition)) {
                    if ($definition instanceof User) {
                        $this->userComponents[$id] = $definition->identityClass;
                    } else {
                        $this->components[$id] = get_class($definition);
                    }

                    continue;
                }

                if (isset($definition['class']) && is_string($definition['class']) && $definition['class'] !== '') {
                    if (
                        $definition['class'] === User::class && isset($definition['identityClass']) &&
                        is_string($definition['identityClass']) && $definition['identityClass'] !== ''
                    ) {
                        $this->userComponents[$id] = $definition['identityClass'];
                    } else {
                        $this->components[$id] = $definition['class'];
                    }
                }
            }
        }
    }

    /**
     * Processes service definitions from the Yii application configuration array.
     *
     * Iterates over the container.definitions section of the provided configuration array, normalizing and registering
     * each service definition by its identifier.
     *
     * This method ensures that all services are mapped to their fully qualified class names for accurate static
     * analysis and type inference.
     *
     * @param array $config Yii application configuration array containing service definitions.
     *
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     * @throws RuntimeException if a runtime error prevents the operation from completing successfully.
     *
     * @phpstan-import-type ServiceType from ServiceMap
     * @phpstan-param ServiceType $config
     */
    private function processDefinition(array $config): void
    {
        if ($config !== []) {
            $definitions = $config['container']['definitions'] ?? [];

            foreach ($definitions as $id => $service) {
                if (is_string($id) === false) {
                    $this->throwErrorWhenIdIsNotString('Definition', gettype($id));
                }

                $this->services[$id] = $this->normalizeDefinition($id, $service);
            }
        }
    }

    /**
     * Processes singleton service definitions from the Yii application configuration array.
     *
     * Iterates over the container.singletons section of the provided configuration array, normalizing and registering
     * each singleton service definition by its identifier.
     *
     * This method ensures that all singleton services are mapped to their fully qualified class names for accurate
     * static analysis and type inference.
     *
     * @param array $config Yii application configuration array containing singleton definitions.
     *
     * @throws ReflectionException if the service definition is invalid or can't be resolved.
     * @throws RuntimeException if a runtime error prevents the operation from completing successfully.
     *
     * @phpstan-import-type ServiceType from ServiceMap
     * @phpstan-param ServiceType $config
     */
    private function processSingletons(array $config): void
    {
        if ($config !== []) {
            $singletons = $config['container']['singletons'] ?? [];

            foreach ($singletons as $id => $service) {
                if (is_string($id) === false) {
                    $this->throwErrorWhenIdIsNotString('Singleton', gettype($id));
                }

                $this->services[$id] = $this->normalizeDefinition($id, $service);
            }
        }
    }

    /**
     * Throws a {@see RuntimeException} when a configuration file section is not an array.
     *
     * This method is invoked when a required section of the Yii application configuration file (such as components,
     * container, container.definitions, or container.singletons) does not contain a valid array.
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
     * This method is invoked when the provided identifier for a service, definition, or component is not of type
     * string, which is required for proper registration and resolution in the Yii application context.
     *
     * It ensures that only valid string identifiers are processed during service and component mapping, providing a
     * clear and descriptive error message for debugging and static analysis.
     *
     * @param string ...$args Arguments describing the context and the invalid identifier type.
     *
     * @throws RuntimeException if a runtime error prevents the operation from completing successfully.
     */
    private function throwErrorWhenIdIsNotString(string ...$args): never
    {
        throw new RuntimeException(sprintf("'%s': ID must be a string, got '%s'.", ...$args));
    }

    /**
     * Throws a {@see RuntimeException} when a service or component definition is unsupported.
     *
     * This method is invoked when the provided definition for a service or component cannot be resolved to a valid
     * class name or does not match any supported configuration pattern.
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
        throw new RuntimeException(sprintf("Unsupported definition for '%s'.", $id));
    }
}
