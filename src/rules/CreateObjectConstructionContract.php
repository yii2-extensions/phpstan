<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\rules;

use PHPStan\Reflection\ClassReflection;
use yii\base\{Action, BaseObject, Controller};

use function count;

/**
 * Represents how an API constructs a configured object: the inherited constructor, the arguments the API supplies, the
 * configuration parameter and what must hold before the configuration is applied.
 *
 * Closed contract with two instances, {@see baseObject()} and {@see externalAction()}.
 */
final readonly class CreateObjectConstructionContract
{
    /**
     * @param string $constructorClass Class that must declare the inherited constructor.
     * @param list<int> $fixedArgumentPositions Positions of the arguments the API supplies, before the configuration.
     * @param int $configurationPosition Position of the configuration parameter, the last one.
     * @param string $configurationName Name of the configuration parameter.
     * @param bool $followsConstructorArguments Whether a `__construct()` option and definition params that address only
     * the configuration parameter are followed; otherwise any of them leaves the configuration unresolved.
     * @param list<string> $propertiesWrittenBeforeConfiguration Properties the constructor assigns before the
     * configuration, which must be plain public properties.
     */
    private function __construct(
        public string $constructorClass,
        public array $fixedArgumentPositions,
        public int $configurationPosition,
        public string $configurationName,
        public bool $followsConstructorArguments,
        public array $propertiesWrittenBeforeConfiguration,
    ) {}

    /**
     * Returns the contract of {@see BaseObject::__construct()}, used by `Yii::createObject()`, `new` and widgets.
     */
    public static function baseObject(): self
    {
        return new self(BaseObject::class, [], 0, 'config', true, []);
    }

    /**
     * Returns the contract of {@see Action::__construct()} for `Yii::createObject($definition, [$id, $this])` in
     * {@see Controller::createAction()}.
     */
    public static function externalAction(): self
    {
        return new self(Action::class, [0, 1], 2, 'config', false, ['id', 'controller']);
    }

    /**
     * Returns whether the class inherits the contract's constructor with one variant whose parameters are the supplied
     * arguments followed by the configuration parameter; optionality isn't compared.
     */
    public function isInheritedConstructor(ClassReflection $classReflection): bool
    {
        if ($classReflection->hasNativeMethod('__construct') === false) {
            return false;
        }

        $constructor = $classReflection->getNativeMethod('__construct');

        if ($constructor->getDeclaringClass()->getName() !== $this->constructorClass) {
            return false;
        }

        $variants = $constructor->getVariants();

        $parameters = count($variants) === 1 ? $variants[0]->getParameters() : [];

        $configuration = $parameters[$this->configurationPosition] ?? null;

        if (
            count($parameters) !== $this->configurationPosition + 1
            || $configuration?->getName() !== $this->configurationName
            || count($this->fixedArgumentPositions) !== $this->configurationPosition
        ) {
            return false;
        }

        foreach ($this->fixedArgumentPositions as $index => $position) {
            if ($position !== $index) {
                return false;
            }
        }

        return true;
    }
}
