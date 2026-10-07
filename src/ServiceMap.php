<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan;

use Closure;
use ReflectionClass;
use ReflectionFunction;
use ReflectionNamedType;
use RuntimeException;
use yii\base\InvalidArgumentException;
use yii\di\Instance;
use yii\web\Application;

use function array_filter;
use function array_is_list;
use function array_key_exists;
use function array_keys;
use function array_values;
use function class_exists;
use function count;
use function dirname;
use function gettype;
use function in_array;
use function is_array;
use function is_callable;
use function is_file;
use function is_object;
use function is_readable;
use function is_string;
use function ltrim;
use function pathinfo;
use function preg_match;
use function realpath;
use function sprintf;
use function str_contains;
use function strtolower;

use const PATHINFO_EXTENSION;

/**
 * Maps and normalizes service and component definitions from Yii Application configuration for static analysis.
 *
 * Loads, validates, and processes configuration files, exposing lookup methods to resolve class names and configuration
 * arrays by identifier. Registers the class of every definition form Yii accepts when it can be determined statically:
 * class name strings, arrays with a `class` or `__class` key, closures returning a single class, and object instances.
 * Definitions whose class can't be determined, such as untyped closures or {@see Instance} references, are skipped and
 * flagged as unresolved, so the lookups return `null` and the consumers type them as `object`.
 */
final class ServiceMap
{
    /**
     * Pattern matching a syntactically valid class name without a leading backslash.
     */
    private const CLASS_NAME_PATTERN = '/^[a-zA-Z_\x80-\xff][\w\x80-\xff]*(?:\\\\[a-zA-Z_\x80-\xff][\w\x80-\xff]*)*$/';

    /**
     * Error message labels of the `container` subsections, indexed by subsection key.
     */
    private const CONTAINER_SECTIONS = ['definitions' => 'Definition', 'singletons' => 'Singleton'];

    /**
     * Return types that refer to the scope of the callable instead of naming a class.
     */
    private const RELATIVE_CLASS_TYPES = ['parent', 'self', 'static'];

    /**
     * Application type for PHPStan analysis.
     *
     * @phpstan-var class-string<\yii\base\Application>|string
     */
    private string $applicationType = '';

    /**
     * Behavior definitions map for Yii Application analysis.
     *
     * @phpstan-var array<string, list<string>>
     */
    private array $behaviors = [];

    /**
     * Component definitions map for Yii Application analysis.
     *
     * @phpstan-var array<string, string>
     */
    private array $components = [];

    /**
     * Component definitions for Yii Application analysis.
     *
     * @phpstan-var array<string, array<array-key, mixed>>
     */
    private array $componentsDefinitions = [];

    /**
     * IDs of components defined by an object, whose class Yii doesn't resolve through the container.
     *
     * @phpstan-var array<string, true>
     */
    private array $objectDefinedComponents = [];

    /**
     * IDs of services defined by an object, whose class Yii doesn't resolve through the container.
     *
     * @phpstan-var array<string, true>
     */
    private array $objectDefinedServices = [];

    /**
     * Application params for PHPStan type inference.
     *
     * @phpstan-var array<array-key, mixed>
     */
    private array $params = [];

    /**
     * Service definitions map for Yii Application analysis.
     *
     * @phpstan-var array<string, string>
     */
    private array $services = [];

    /**
     * IDs of components defined in the configuration whose class can't be determined.
     *
     * @phpstan-var array<string, true>
     */
    private array $unresolvedComponents = [];

    /**
     * IDs of services with a non-empty definition whose class can't be determined.
     *
     * @phpstan-var array<string, true>
     */
    private array $unresolvedServices = [];

    /**
     * Creates a new instance of the {@see ServiceMap} class.
     *
     * @param string $configPath Path to the Yii Application configuration file (default: `''`). If provided, the
     * configuration file must exist and be valid. If empty or not provided, operates with empty service/component maps.
     *
     * @throws InvalidArgumentException if one or more arguments are invalid, of incorrect type or format.
     * @throws RuntimeException if a runtime error prevents the operation from completing successfully.
     */
    public function __construct(string $configPath = '')
    {
        if ($configPath !== '') {
            $resolvedPath = realpath($configPath);

            if (
                $resolvedPath === false
                || is_file($resolvedPath) === false
                || is_readable($resolvedPath) === false
                || pathinfo($resolvedPath, PATHINFO_EXTENSION) !== 'php'
            ) {
                throw new InvalidArgumentException(
                    sprintf('Provided config path \'%s\' must be a readable PHP file.', $configPath),
                );
            }

            $configPath = $resolvedPath;
        }

        require_once dirname(__DIR__) . '/bootstrap.php';

        $config = $this->loadConfig($configPath);

        $this->applicationType = $config['applicationType'];
        $this->params = $config['params'];

        $this->processBehaviors($config['behaviors']);
        $this->processComponents($config['components']);

        foreach ($config['containerSections'] as [$label, $services]) {
            $this->processServices($services, $label);
        }

        $this->resolveContainerAliases();
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
        return $this->componentsDefinitions[$id] ?? [];
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
     * Returns whether a component is defined in the configuration but its class can't be determined.
     *
     * Covers closures without a single class return type, {@see Instance} references, array callables, arrays without
     * a class key, and classes naming a container ID on a cycle or a container ID whose class can't be determined.
     * Components that are absent or set to `null` aren't unresolved.
     *
     * @param string $id Component identifier to look up.
     *
     * @return bool `true` if the component is defined with an unknown class; `false` otherwise.
     */
    public function isUnresolvedComponent(string $id): bool
    {
        return isset($this->unresolvedComponents[$id]);
    }

    /**
     * Returns whether a container service is defined in the configuration but its class can't be determined.
     *
     * Covers factories without a single class return type, {@see Instance} references, array callables, arrays without
     * a class key under an ID that doesn't name an instantiable class, and definitions naming a container ID on a cycle
     * or a container ID whose class can't be determined. Services that are absent, or defined empty, aren't unresolved,
     * since Yii then uses the ID itself as the class.
     *
     * @param string $id Service identifier to look up.
     *
     * @return bool `true` if the service is defined with an unknown class; `false` otherwise.
     */
    public function isUnresolvedService(string $id): bool
    {
        return isset($this->unresolvedServices[$id]);
    }

    /**
     * Follows a chain of container IDs from a class name to the class it resolves to.
     *
     * The chain stops at a class that names no other container ID, at a container ID defined by an object, whose class
     * is returned as is, and at a container ID whose class can't be determined.
     *
     * @param string $className Class name that may name a container ID.
     * @param array $visited Container IDs already on the chain.
     *
     * @return string|null Class name the chain resolves to, or `null` when the chain is a cycle or reaches a container
     * ID whose class can't be determined.
     *
     * @phpstan-param array<string, true> $visited
     */
    private function followContainerAliases(string $className, array $visited): string|null
    {
        while (isset($this->services[$className]) && $this->services[$className] !== $className) {
            if (isset($visited[$className])) {
                return null;
            }

            if (isset($this->objectDefinedServices[$className])) {
                return $this->services[$className];
            }

            $visited[$className] = true;
            $className = $this->services[$className];
        }

        return isset($this->unresolvedServices[$className]) ? null : $className;
    }

    /**
     * Loads, validates, and normalizes the Yii Application configuration file for static analysis.
     *
     * Ensures the configuration file returns an array, that `phpstan.application_type` is a string, and that the
     * `phpstan`, `behaviors`, `components`, `params`, `container`, `container.definitions`, and `container.singletons`
     * sections are arrays when present. Absent or `null` sections resolve to empty arrays, except `params`, which must
     * be an array when its key is present. The `container` subsections keep the order the configuration lists them in,
     * since {@see \Yii::configure()} applies them in that order and a later entry replaces an earlier one with the same
     * ID.
     *
     * @param string $configPath Path to the Yii Application configuration file. If empty, every section is empty.
     *
     * @throws RuntimeException if the file doesn't return an array or a section has an unsupported type.
     *
     * @return array Normalized configuration sections.
     *
     * @phpstan-return array{
     *     applicationType: string,
     *     behaviors: array<array-key, mixed>,
     *     components: array<array-key, mixed>,
     *     containerSections: list<array{string, array<array-key, mixed>}>,
     *     params: array<array-key, mixed>,
     * }
     */
    private function loadConfig(string $configPath): array
    {
        $config = $configPath !== '' ? require $configPath : [];

        if (is_array($config) === false) {
            throw new RuntimeException(
                sprintf("Configuration file '%s' must return an array.", $configPath),
            );
        }

        $applicationType = $this->section($config, 'phpstan', $configPath, 'phpstan')['application_type'] ?? '';

        if (is_string($applicationType) === false) {
            $this->throwErrorWhenIsNotString('Application type', 'phpstan.application_type', gettype($applicationType));
        }

        $behaviors = $this->section($config, 'behaviors', $configPath, 'behaviors');
        $components = $this->section($config, 'components', $configPath, 'components');

        $params = array_key_exists('params', $config) ? $config['params'] : [];

        if (is_array($params) === false) {
            $this->throwErrorWhenConfigFileIsNotArray($configPath, 'params');
        }

        $container = $this->section($config, 'container', $configPath, 'container');
        $containerSections = [];

        foreach (array_keys($container) as $key) {
            if (isset(self::CONTAINER_SECTIONS[$key])) {
                $containerSections[] = [
                    self::CONTAINER_SECTIONS[$key],
                    $this->section($container, $key, $configPath, 'container.' . $key),
                ];
            }
        }

        return [
            'applicationType' => $applicationType !== '' ? $applicationType : Application::class,
            'behaviors' => $behaviors,
            'components' => $components,
            'containerSections' => $containerSections,
            'params' => $params,
        ];
    }

    /**
     * Normalizes a class name by removing its leading backslash.
     *
     * @param string $class Class name, with or without a leading backslash.
     *
     * @return string|null Class name without a leading backslash, or `null` if it's empty.
     */
    private function normalizeClassName(string $class): string|null
    {
        $class = ltrim($class, '\\');

        return $class !== '' ? $class : null;
    }

    /**
     * Registers the behavior class names attached to each class in the `behaviors` configuration section.
     *
     * Non-string entries in a behavior list are ignored.
     *
     * @param array $behaviors Behavior lists indexed by the fully qualified class name they're attached to.
     *
     * @throws RuntimeException if a behavior ID is not a string, or if a behavior definition is not an array.
     *
     * @phpstan-param array<array-key, mixed> $behaviors
     */
    private function processBehaviors(array $behaviors): void
    {
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

    /**
     * Registers the class name and remaining configuration of each component in the `components` configuration section.
     *
     * Components whose class can't be determined are skipped. Array definitions with a resolved class also keep their
     * other keys as the component definition. Components defined by an object are recorded as such, since Yii returns
     * the object, or calls the closure, instead of resolving a class through the container.
     *
     * @param array $components Component definitions indexed by component ID.
     *
     * @throws RuntimeException if a component ID is not a string, or if a definition is a scalar other than a `string`.
     *
     * @phpstan-param array<array-key, mixed> $components
     */
    private function processComponents(array $components): void
    {
        foreach ($components as $id => $definition) {
            if (is_string($id) === false) {
                $this->throwErrorWhenIsNotString('Component', 'ID', gettype($id));
            }

            $className = $this->resolveComponentClass($id, $definition);

            if ($className === null) {
                if ($definition !== null) {
                    $this->unresolvedComponents[$id] = true;
                }

                continue;
            }

            $this->components[$id] = $className;

            if (is_object($definition)) {
                $this->objectDefinedComponents[$id] = true;
            }

            if (is_array($definition)) {
                unset($definition['class'], $definition['__class']);

                $this->componentsDefinitions[$id] = $definition;
            }
        }
    }

    /**
     * Registers the class name resolved for each service in a `container` configuration subsection.
     *
     * Each entry replaces whatever an earlier entry recorded for its ID, as {@see \yii\di\Container::set()} and
     * {@see \yii\di\Container::setSingleton()} replace the definition. Services whose class can't be determined are
     * skipped, and recorded as unresolved unless the definition is empty, since Yii then uses the ID itself as the
     * class. Services defined by an object are recorded as such, since Yii returns the object, or calls it, instead of
     * resolving a class through the container.
     *
     * @param array $services Service definitions indexed by service ID.
     * @param string $label Label used in error messages to identify the subsection (`'Definition'` or `'Singleton'`).
     *
     * @throws RuntimeException if a service ID is not a string, or if a definition is a scalar other than a `string`.
     *
     * @phpstan-param array<array-key, mixed> $services
     */
    private function processServices(array $services, string $label): void
    {
        foreach ($services as $id => $definition) {
            if (is_string($id) === false) {
                $this->throwErrorWhenIsNotString($label, 'ID', gettype($id));
            }

            $definition = $this->unwrapServiceDefinition($definition);
            $className = $this->resolveServiceClass($id, $definition);

            unset($this->services[$id], $this->objectDefinedServices[$id], $this->unresolvedServices[$id]);

            if ($className !== null) {
                $this->services[$id] = $className;

                if (is_object($definition)) {
                    $this->objectDefinedServices[$id] = true;
                }
            } elseif ($definition !== null && $definition !== []) {
                $this->unresolvedServices[$id] = true;
            }
        }
    }

    /**
     * Resolves the class of an array definition from the first of two class keys that is set.
     *
     * @param array $definition Configuration array.
     * @param string $key Class key that takes precedence.
     * @param string $fallbackKey Class key read when `$key` is not set.
     *
     * @return string|null Class name without a leading backslash, or `null` if neither key holds a non-empty `string`.
     *
     * @phpstan-param array<array-key, mixed> $definition
     */
    private function resolveArrayClass(array $definition, string $key, string $fallbackKey): string|null
    {
        $class = $definition[$key] ?? $definition[$fallbackKey] ?? null;

        return is_string($class) ? $this->normalizeClassName($class) : null;
    }

    /**
     * Resolves the class of a component definition as {@see \yii\di\ServiceLocator::set()} and
     * {@see \yii\di\ServiceLocator::get()} interpret it.
     *
     * A closure is a factory resolved from its return type; any other object is the component itself. An array
     * resolves from its `__class` key, then its `class` key; array callables and arrays without a class key resolve to
     * `null`, the latter because Yii completes core components from the application class.
     *
     * @param string $id Component ID.
     * @param mixed $definition Component definition.
     *
     * @throws RuntimeException if the definition is a scalar other than a `string`.
     *
     * @return string|null Class name without a leading backslash, or `null` if it can't be determined.
     */
    private function resolveComponentClass(string $id, mixed $definition): string|null
    {
        if ($definition === null) {
            return null;
        }

        if (is_array($definition)) {
            return is_callable($definition, true) ? null : $this->resolveArrayClass($definition, '__class', 'class');
        }

        return $this->resolveScalarOrObjectClass($id, $definition, $definition instanceof Closure);
    }

    /**
     * Resolves the service and component classes that name another container ID through that ID's resolved class.
     *
     * {@see \yii\di\Container::get()} resolves the class of a string or array definition recursively when it's another
     * container ID, and {@see \yii\di\ServiceLocator::get()} creates a string or array component through
     * {@see \Yii::createObject()}, which resolves its class through the container as well. Object definitions keep
     * their class, the declared return type of a closure or of an object with an `__invoke()` method, or the class of
     * any other object, since Yii returns what the object yields. `container.definitions` and `container.singletons`
     * share one ID space, as in the container. Chains are followed; a service or component whose chain is a cycle or
     * reaches a container ID whose class can't be determined is dropped and flagged as unresolved. A class that names
     * no other container ID is kept.
     */
    private function resolveContainerAliases(): void
    {
        $services = [];

        foreach ($this->services as $id => $className) {
            $resolved = isset($this->objectDefinedServices[$id])
                ? $className
                : $this->followContainerAliases($className, [$id => true]);

            if ($resolved !== null) {
                $services[$id] = $resolved;
            } else {
                $this->unresolvedServices[$id] = true;
            }
        }

        foreach ($this->components as $id => $className) {
            if (isset($this->objectDefinedComponents[$id])) {
                continue;
            }

            $resolved = $this->followContainerAliases($className, []);

            if ($resolved === null) {
                unset($this->components[$id], $this->componentsDefinitions[$id]);

                $this->unresolvedComponents[$id] = true;
            } else {
                $this->components[$id] = $resolved;
            }
        }

        $this->services = $services;
    }

    /**
     * Resolves the class returned by a factory callable from its declared return type.
     *
     * @param ReflectionFunction $factory Reflection of the closure, or of the closure created from an object with an
     * `__invoke()` method.
     *
     * @return string|null Class name, or `null` if the return type is missing, nullable, a union, an intersection, a
     * builtin type, `self`, `static`, or `parent`.
     */
    private function resolveFactoryClass(ReflectionFunction $factory): string|null
    {
        $returnType = $factory->getReturnType();

        if ($returnType instanceof ReflectionNamedType === false || $returnType->isBuiltin() || $returnType->allowsNull()) {
            return null;
        }

        $class = $returnType->getName();

        return in_array(strtolower($class), self::RELATIVE_CLASS_TYPES, true) ? null : $class;
    }

    /**
     * Resolves a service ID used as its own class, as {@see \yii\di\Container::normalizeDefinition()} does for an empty
     * definition and for an array definition without a class key.
     *
     * {@see \yii\di\Container::build()} constructs any instantiable class, whether or not it extends
     * {@see \yii\base\BaseObject}, and throws for an interface, an abstract class, or an enum. Only syntactically valid
     * class names are passed to {@see class_exists()}, so arbitrary IDs never reach the autoloaders.
     *
     * @param string $id Service ID.
     *
     * @return string|null ID without a leading backslash when it names an instantiable class, or `null` otherwise.
     */
    private function resolveIdAsClass(string $id): string|null
    {
        $class = ltrim($id, '\\');

        return preg_match(self::CLASS_NAME_PATTERN, $class) === 1
            && class_exists($class)
            && (new ReflectionClass($class))->isInstantiable()
            ? $class
            : null;
    }

    /**
     * Resolves the class of a definition that is neither an array nor `null`.
     *
     * @param string $id Component or service ID.
     * @param mixed $definition Definition to resolve.
     * @param bool $isFactory Whether an object definition is a factory callable instead of the instance itself.
     *
     * @throws RuntimeException if the definition is a scalar other than a `string`.
     *
     * @return string|null Class name without a leading backslash, or `null` if it can't be determined.
     */
    private function resolveScalarOrObjectClass(string $id, mixed $definition, bool $isFactory): string|null
    {
        if (is_string($definition)) {
            return $this->normalizeClassName($definition);
        }

        if ($definition instanceof Instance) {
            return null;
        }

        if (is_object($definition)) {
            return $isFactory && is_callable($definition)
                ? $this->resolveFactoryClass(new ReflectionFunction(Closure::fromCallable($definition)))
                : $definition::class;
        }

        $this->throwErrorWhenUnsupportedDefinition($id);
    }

    /**
     * Resolves the class of a container definition as {@see \yii\di\Container::setDefinitions()} and
     * {@see \yii\di\Container::normalizeDefinition()} interpret it.
     *
     * An empty definition resolves to the ID itself; a callable object is a factory resolved from its return type; an
     * array resolves from its `class` key, then its `__class` key, then the ID when the ID contains a namespace
     * separator. Array callables resolve to `null`.
     *
     * @param string $id Service ID.
     * @param mixed $definition Service definition, already unwrapped by {@see unwrapServiceDefinition()}.
     *
     * @throws RuntimeException if the definition is a scalar other than a `string`.
     *
     * @return string|null Class name without a leading backslash, or `null` if it can't be determined.
     */
    private function resolveServiceClass(string $id, mixed $definition): string|null
    {
        if ($definition === null || $definition === []) {
            return $this->resolveIdAsClass($id);
        }

        if (is_array($definition) === false) {
            return $this->resolveScalarOrObjectClass($id, $definition, true);
        }

        if (is_callable($definition, true)) {
            return null;
        }

        return $this->resolveArrayClass($definition, 'class', '__class')
            ?? (str_contains($id, '\\') ? $this->resolveIdAsClass($id) : null);
    }

    /**
     * Returns a configuration section, or an empty array when the section is absent or `null`.
     *
     * @param array $config Configuration array holding the section.
     * @param string $key Key of the section in `$config`.
     * @param string $configPath Path to the configuration file, used in the error message.
     * @param string $path Dotted path of the section, used in the error message.
     *
     * @throws RuntimeException if the section is set and is not an array.
     *
     * @return array Configuration section.
     *
     * @phpstan-param array<array-key, mixed> $config
     * @phpstan-return array<array-key, mixed>
     */
    private function section(array $config, string $key, string $configPath, string $path): array
    {
        $section = $config[$key] ?? [];

        if (is_array($section) === false) {
            $this->throwErrorWhenConfigFileIsNotArray($configPath, $path);
        }

        return $section;
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

    /**
     * Unwraps a `[definition, params]` list to its definition, as {@see \yii\di\Container::setDefinitions()} and
     * {@see \yii\di\Container::setSingletons()} do.
     *
     * Any other list, such as one holding a single array definition, is kept as is, since Yii doesn't unwrap it either.
     *
     * @param mixed $definition Service definition.
     *
     * @return mixed First element of the list, or the definition itself when it's not such a list.
     */
    private function unwrapServiceDefinition(mixed $definition): mixed
    {
        return is_array($definition)
            && array_is_list($definition)
            && count($definition) === 2
            && is_array($definition[1])
            ? $definition[0]
            : $definition;
    }
}
