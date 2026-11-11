<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\rules;

use yii\base\Component;
use yii2\extensions\phpstan\exception\Message;

use function is_int;

/**
 * Represents a behavior definition that {@see Component} can't attach, as found by {@see BehaviorDefinitionResolver}.
 */
final readonly class InvalidBehaviorDefinition
{
    /**
     * Stores an unattachable behavior definition and its diagnostic location.
     *
     * @param string $owner Class whose `behaviors()` returns the definition, or receiver type of the attach call, as
     * PHPStan displays it.
     * @param string $method Method that supplies the definition: `behaviors`, `attachBehavior` or `attachBehaviors`.
     * @param int|string|null $name Behavior name: a string for a named behavior, an `int` for an anonymous one, or
     * `null` when the name isn't a single constant.
     * @param int $line Line to report: the list item, the definition argument or the return statement.
     * @param string|null $definedClass Class or container ID that the definition names, or `null` for a configuration
     * array without a class.
     * @param string|null $resolvedClass Class that the container resolves the defined class to, or `null` when the
     * definition has no class.
     */
    public function __construct(
        public string $owner,
        public string $method,
        public int|string|null $name,
        public int $line,
        public string|null $definedClass,
        public string|null $resolvedClass,
    ) {}

    /**
     * Returns the diagnostic message, naming the behavior, its owner and method, and the defined and resolved classes.
     */
    public function message(): string
    {
        $behavior = match (true) {
            $this->name === null => 'with a non-constant name',
            is_int($this->name) => "#{$this->name}",
            default => "'{$this->name}'",
        };

        return match (true) {
            $this->definedClass === null
                || $this->resolvedClass === null => Message::BEHAVIOR_INVALID_DEFINITION_ARRAY->getMessage(
                    $behavior,
                    $this->owner,
                    $this->method,
                ),
            $this->definedClass === $this->resolvedClass => Message::BEHAVIOR_INVALID_DEFINITION_CLASS->getMessage(
                $behavior,
                $this->owner,
                $this->method,
                $this->resolvedClass,
            ),
            default => Message::BEHAVIOR_INVALID_DEFINITION_RESOLVED_CLASS->getMessage(
                $behavior,
                $this->owner,
                $this->method,
                $this->definedClass,
                $this->resolvedClass,
            ),
        };
    }
}
