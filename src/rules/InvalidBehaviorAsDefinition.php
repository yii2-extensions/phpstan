<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\rules;

use yii\base\Component;
use yii2\extensions\phpstan\exception\Message;

/**
 * Represents an `as` option of a `Yii::createObject()` configuration that {@see Component} rejects, as found by
 * {@see BehaviorDefinitionResolver}.
 */
final readonly class InvalidBehaviorAsDefinition
{
    /**
     * Stores a rejected `as` behavior definition and its diagnostic location.
     *
     * @param string $calledClass Class through which `createObject()` is called, as PHPStan displays it.
     * @param string $owner Created component that receives the option, as PHPStan displays it.
     * @param string $option Option name as written, including the `as ` prefix.
     * @param int $line Line to report: the option key, or the call when the configuration doesn't locate it.
     * @param string|null $definedClass Class that the option names, or `null` for a configuration array without a class
     * or a rejected value.
     * @param string|null $resolvedClass Class that the container resolves the named behavior class to, or `null` when
     * `Component::__set()` rejects the option itself.
     * @param string|null $valueType Description of a rejected value that is no class name or configuration array, or
     * `null` otherwise.
     */
    public function __construct(
        public string $calledClass,
        public string $owner,
        public string $option,
        public int $line,
        public string|null $definedClass,
        public string|null $resolvedClass,
        public string|null $valueType,
    ) {}

    /**
     * Returns the diagnostic message, naming the call, the option, the owner and the defined and resolved classes.
     */
    public function message(): string
    {
        return match (true) {
            $this->valueType !== null => Message::BEHAVIOR_INVALID_AS_DEFINITION_VALUE->getMessage(
                $this->calledClass,
                $this->option,
                $this->owner,
                $this->valueType,
            ),
            $this->definedClass === null => Message::BEHAVIOR_INVALID_AS_DEFINITION_ARRAY->getMessage(
                $this->calledClass,
                $this->option,
                $this->owner,
            ),
            $this->resolvedClass === null => Message::BEHAVIOR_INVALID_AS_DEFINITION_CLASS->getMessage(
                $this->calledClass,
                $this->option,
                $this->owner,
                $this->definedClass,
            ),
            default => Message::BEHAVIOR_INVALID_AS_DEFINITION_RESOLVED_CLASS->getMessage(
                $this->calledClass,
                $this->option,
                $this->owner,
                $this->definedClass,
                $this->resolvedClass,
            ),
        };
    }
}
