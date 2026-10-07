<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan;

use PHPStan\Analyser\ResultCache\ResultCacheValueExtension;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\{Type, VerbosityLevel};

use function count;
use function hash;
use function implode;
use function is_string;
use function serialize;
use function strpos;
use function substr;

/**
 * Exposes the Yii configuration values that drive inferred types to the PHPStan result cache.
 *
 * Extensions record a value dependency (`trackValueDependency()`) on one of the keys built by this class whenever they
 * read the configuration on behalf of analysed code, including when the configuration has no entry for the requested
 * id. On the next run PHPStan compares {@see getValue()} with the recorded value and re-analyses only the files whose
 * values changed, which also covers configuration composed from several files through `require`.
 *
 * {@see ResultCacheValueExtension} for PHPStan result cache value extension contract.
 * {@see ServiceMap} for service and component map for Yii Application static analysis.
 */
final class ServiceMapResultCacheValueExtension implements ResultCacheValueExtension
{
    /**
     * Key of the configured application class.
     */
    public const APPLICATION_KEY = 'application';

    /**
     * Value returned for keys whose id has no configuration entry.
     */
    public const MISSING = 'missing';

    /**
     * Key of the array shape inferred for the application params.
     */
    public const PARAMS_KEY = 'params';

    /**
     * Value returned for a component or service defined in the configuration whose class can't be determined.
     */
    public const UNRESOLVED = 'unresolved';

    /**
     * Value returned for keys this class doesn't build, such as keys stored by another version of the extension.
     */
    public const UNSUPPORTED = 'unsupported';

    /**
     * Key prefix for the behaviors attached to a class.
     */
    private const BEHAVIORS = 'behaviors';

    /**
     * Key prefix for application components.
     */
    private const COMPONENT = 'component';

    /**
     * Key prefix for DI container definitions and singletons.
     */
    private const SERVICE = 'service';

    /**
     * Params value computed on first use, since PHPStan asks for it again for every analysed file reading params.
     */
    private string|null $paramsValue = null;

    /**
     * @param ServiceMap $serviceMap Service and component map for Yii Application static analysis.
     * @param array<string, string> $genericComponents Map of component id to the definition key holding its generic
     * type argument (`yii2.component_generics`).
     */
    public function __construct(
        private readonly ServiceMap $serviceMap,
        private readonly array $genericComponents = [],
    ) {}

    /**
     * Returns the key for the behaviors attached to the given class.
     *
     * @param string $className Fully qualified class name the behaviors are configured for.
     */
    public static function behaviorsKey(string $className): string
    {
        return self::BEHAVIORS . ':' . $className;
    }

    /**
     * Returns the key for the application component with the given id.
     *
     * @param string $id Component id.
     */
    public static function componentKey(string $id): string
    {
        return self::COMPONENT . ':' . $id;
    }

    /**
     * Returns the current configuration value for the given key.
     *
     * Each value covers exactly what the extensions derive types from:
     *
     * - `application`: the configured application class.
     * - `params`: a hash of the params shape built by {@see ParamsTypeBuilder}, or {@see MISSING} when no params are
     *   configured.
     * - `component:<id>`: a hash of the component class and its generic type argument, {@see UNRESOLVED} when the
     *   component is defined with a class that can't be determined, or {@see MISSING}.
     * - `service:<id>`: the configured service class, {@see UNRESOLVED} when the service is defined with a class that
     *   can't be determined, or {@see MISSING}.
     * - `behaviors:<class>`: a hash of the ordered behavior classes, or {@see MISSING} when none are attached.
     *
     * Any other key yields {@see UNSUPPORTED}, so a result cache written by another version of the extension
     * re-analyses the files depending on it instead of failing the run.
     *
     * @param string $key Key built by this class.
     *
     * @return string Value compared with the one recorded by the previous analysis.
     */
    public function getValue(string $key): string
    {
        $separator = strpos($key, ':');
        $kind = $separator === false ? $key : substr($key, 0, $separator);
        $id = $separator === false ? '' : substr($key, $separator + 1);

        return match ($kind) {
            self::APPLICATION_KEY => $this->serviceMap->getApplicationType(),
            self::PARAMS_KEY => $this->getParamsValue(),
            self::COMPONENT => $this->getComponentValue($id),
            self::SERVICE => $this->getServiceValue($id),
            self::BEHAVIORS => $this->getBehaviorsValue($id),
            default => self::UNSUPPORTED,
        };
    }

    /**
     * Returns the key unchanged, since keys hold ids and class names rather than file paths.
     */
    public function keyFromResultCache(string $storedKey): string
    {
        return $storedKey;
    }

    /**
     * Returns the key unchanged, since keys hold ids and class names rather than file paths.
     */
    public function keyToResultCache(string $key): string
    {
        return $key;
    }

    /**
     * Returns the key for the DI container definition or singleton with the given id.
     *
     * @param string $id Service id.
     */
    public static function serviceKey(string $id): string
    {
        return self::SERVICE . ':' . $id;
    }

    /**
     * Describes a params type as nested arrays mirroring its shape keys, with the precise description of every value
     * that isn't a shape as leaf.
     *
     * Unlike the description of the whole shape, whose keys aren't escaped, its serialization differs for every
     * distinct shape, whatever its keys hold. List shapes need no marker, since they follow from their keys.
     *
     * @return array<array-key, mixed>|string Shape keys mapped to the description of their values, or the precise
     * description of a type that isn't a shape.
     */
    private static function describeParamsType(Type $type): array|string
    {
        $constantArrays = $type->getConstantArrays();

        if (count($constantArrays) !== 1) {
            return $type->describe(VerbosityLevel::precise());
        }

        $valueTypes = $constantArrays[0]->getValueTypes();
        $description = [];

        foreach ($constantArrays[0]->getKeyTypes() as $position => $keyType) {
            $description[$keyType->getValue()] = self::describeParamsType(
                $valueTypes[$position] ?? throw new ShouldNotHappenException('Shape key without a value type.'),
            );
        }

        return $description;
    }

    /**
     * Returns the value for the behaviors attached to the given class.
     */
    private function getBehaviorsValue(string $className): string
    {
        $behaviors = $this->serviceMap->getBehaviorsByClassName($className);

        return $behaviors === [] ? self::MISSING : hash('sha256', implode("\n", $behaviors));
    }

    /**
     * Returns the value for the component with the given id.
     */
    private function getComponentValue(string $id): string
    {
        $componentClass = $this->serviceMap->getComponentClassById($id);

        if ($componentClass === null) {
            return $this->serviceMap->isUnresolvedComponent($id) ? self::UNRESOLVED : self::MISSING;
        }

        $genericProperty = $this->genericComponents[$id] ?? null;
        $genericType = $genericProperty !== null
            ? $this->serviceMap->getComponentDefinitionById($id)[$genericProperty] ?? null
            : null;

        return hash('sha256', $componentClass . "\n" . (is_string($genericType) ? $genericType : ''));
    }

    /**
     * Returns the value for the application params.
     */
    private function getParamsValue(): string
    {
        if ($this->paramsValue !== null) {
            return $this->paramsValue;
        }

        $params = $this->serviceMap->getParams();

        return $this->paramsValue = $params === []
            ? self::MISSING
            : hash('sha256', serialize(self::describeParamsType(ParamsTypeBuilder::build($params))));
    }

    /**
     * Returns the value for the service with the given id.
     */
    private function getServiceValue(string $id): string
    {
        return $this->serviceMap->getServiceById($id)
            ?? ($this->serviceMap->isUnresolvedService($id) ? self::UNRESOLVED : self::MISSING);
    }
}
