<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\rules;

use yii\base\Behavior;
use yii2\extensions\phpstan\exception\Message;

use function is_int;

/**
 * Represents an option of a behavior configuration that the created {@see Behavior} can't assign, as found by
 * {@see BehaviorDefinitionResolver}.
 */
final readonly class UnknownBehaviorOption
{
    /**
     * @param string $owner Class whose `behaviors()` returns the configuration, receiver type of the attach call, or
     * component created by `createObject()`, as PHPStan displays it.
     * @param string $method Method that supplies the configuration: `behaviors`, `attachBehavior`, `attachBehaviors` or
     * `createObject`.
     * @param int|string|null $name Behavior name: a string for a named behavior, an `int` for an anonymous one, `null`
     * when the name isn't a single constant, or the `as` option name as written for `createObject`.
     * @param int $line Line to report: the option key, else the list item, the definition argument, the `as` option,
     * the return statement or the call.
     * @param string $behaviorClass Behavior class that Yii creates, as PHPStan displays it.
     * @param string $option Name of the option that the behavior class can't assign.
     * @param string|null $calledClass Class through which `createObject()` is called, for an `as` option, or `null`.
     */
    public function __construct(
        public string $owner,
        public string $method,
        public int|string|null $name,
        public int $line,
        public string $behaviorClass,
        public string $option,
        public string|null $calledClass = null,
    ) {}

    /**
     * Returns the diagnostic message, naming the behavior, its owner and source, the behavior class and the option.
     */
    public function message(): string
    {
        if ($this->calledClass !== null) {
            return Message::BEHAVIOR_UNKNOWN_AS_OPTION->getMessage(
                $this->calledClass,
                (string) $this->name,
                $this->owner,
                $this->behaviorClass,
                $this->option,
            );
        }

        $behavior = match (true) {
            $this->name === null => 'with a non-constant name',
            is_int($this->name) => "#{$this->name}",
            default => "'{$this->name}'",
        };

        return Message::BEHAVIOR_UNKNOWN_OPTION->getMessage(
            $behavior,
            $this->owner,
            $this->method,
            $this->behaviorClass,
            $this->option,
        );
    }
}
