<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\exception;

use function sprintf;

/**
 * Defines PHPStan extension exception message templates.
 *
 * Use {@see Message::getMessage()} to format the template with `sprintf()` arguments.
 */
enum Message: string
{
    /**
     * Error when the configured application type doesn't name `yii\base\Application` or a class extending it.
     */
    case APPLICATION_TYPE_NOT_APPLICATION_CLASS = "Application type '%s' in 'phpstan.application_type' is %s; it must be "
        . "'yii\\base\\Application' or a class extending it that PHPStan can discover.";

    /**
     * Error when a behavior definition is not an array.
     */
    case BEHAVIOR_DEFINITION_NOT_ARRAY = "Behavior definition for '%s' must be an array.";

    /**
     * Error when an `as` option holds a configuration array that `Component::__set()` rejects for lack of a class.
     */
    case BEHAVIOR_INVALID_AS_DEFINITION_ARRAY = "%s::createObject() option '%s' of %s is a configuration array "
        . "without a non-null '__class' or 'class' element, which yii\\base\\Component::__set() rejects.";

    /**
     * Error when an `as` option names a class that isn't a strict subclass of `yii\base\Behavior`.
     */
    case BEHAVIOR_INVALID_AS_DEFINITION_CLASS = "%s::createObject() option '%s' of %s uses %s, which "
        . 'yii\base\Component::__set() rejects: it is not a subclass of yii\base\Behavior.';

    /**
     * Error when the container resolves the behavior class of an `as` option to an object Yii can't attach.
     */
    case BEHAVIOR_INVALID_AS_DEFINITION_RESOLVED_CLASS = "%s::createObject() option '%s' of %s uses %s, which the "
        . 'container resolves to %s; Yii cannot attach it: it is not a yii\base\Behavior or an invocable object.';

    /**
     * Error when an `as` option holds a value that `Component::__set()` never accepts.
     */
    case BEHAVIOR_INVALID_AS_DEFINITION_VALUE = "%s::createObject() option '%s' of %s is %s, which "
        . 'yii\base\Component::__set() rejects: it is not a yii\base\Behavior, a Closure, a class name or a '
        . 'configuration array.';

    /**
     * Error when a behavior configuration of an `as` option sets a value that the behavior class never accepts.
     */
    case BEHAVIOR_INVALID_AS_OPTION_TYPE = "%s::createObject() option '%s' of %s sets %s::\$%s to %s, but %s accepts "
        . 'only %s, even with type coercion.';

    /**
     * Error when a behavior configuration array has no class element that `Yii::createObject()` can read.
     */
    case BEHAVIOR_INVALID_DEFINITION_ARRAY = 'Behavior %s in %s::%s() is a configuration array without a non-null '
        . "'__class' or 'class' element, which Yii::createObject() rejects.";

    /**
     * Error when a behavior definition names a class that Yii can't attach.
     */
    case BEHAVIOR_INVALID_DEFINITION_CLASS = 'Behavior %s in %s::%s() uses %s, which Yii cannot attach: it is not a '
        . 'yii\base\Behavior and has no public attach() method.';

    /**
     * Error when the container resolves a behavior definition to a class that Yii can't attach.
     */
    case BEHAVIOR_INVALID_DEFINITION_RESOLVED_CLASS = 'Behavior %s in %s::%s() uses %s, which the container resolves '
        . 'to %s; Yii cannot attach it: it is not a yii\base\Behavior and has no public attach() method.';

    /**
     * Error when a behavior configuration sets a value that the behavior class never accepts.
     */
    case BEHAVIOR_INVALID_OPTION_TYPE = 'Behavior %s in %s::%s() sets %s::$%s to %s, but %s accepts only %s, even with '
        . 'type coercion.';

    /**
     * Error when no attached behavior provides the requested method.
     */
    case BEHAVIOR_METHOD_NOT_FOUND = 'Method %s::%s() is not provided by any behavior attached to the class.';

    /**
     * Error when no attached behavior provides the requested property.
     */
    case BEHAVIOR_PROPERTY_NOT_FOUND = 'Property %s::$%s is not provided by any behavior attached to the class.';

    /**
     * Error when a behavior configuration of an `as` option sets an option that the behavior class can't assign.
     */
    case BEHAVIOR_UNKNOWN_AS_OPTION = "%s::createObject() option '%s' of %s sets unknown property %s::\$%s: the "
        . 'class has no public property or setter with that name.';

    /**
     * Error when a behavior configuration sets an option that the behavior class can't assign.
     */
    case BEHAVIOR_UNKNOWN_OPTION = 'Behavior %s in %s::%s() sets unknown property %s::$%s: the class has no public '
        . 'property or setter with that name.';

    /**
     * Error when the configuration file does not return an array.
     */
    case CONFIG_FILE_NOT_ARRAY = "Configuration file '%s' must return an array.";

    /**
     * Error when the provided configuration path is not a readable PHP file.
     */
    case CONFIG_PATH_NOT_READABLE = "Provided config path '%s' must be a readable PHP file.";

    /**
     * Error when a configuration file section is not an array.
     */
    case CONFIG_SECTION_NOT_ARRAY = "Configuration file '%s' must contain a valid '%s' 'array'.";

    /**
     * Error when a `createObject()` option value is never accepted by the property or setter Yii assigns it to.
     */
    case CREATE_OBJECT_INVALID_OPTION_TYPE = '%s::createObject() sets %s::$%s to %s, but %s accepts only %s, even with '
        . 'type coercion.';

    /**
     * Error when a literal `createObject()` option names no public property or setter of the created class.
     */
    case CREATE_OBJECT_UNKNOWN_OPTION = '%s::createObject() sets unknown property %s::$%s: the class has no public '
        . 'property or setter with that name.';

    /**
     * Error when an external action option value is never accepted by the property or setter Yii assigns it to.
     */
    case EXTERNAL_ACTION_INVALID_OPTION_TYPE = "External action '%s' in %s::actions() sets %s::\$%s to %s, but %s accepts "
        . 'only %s, even with type coercion.';

    /**
     * Error when an external action option names no public property or setter of the created action.
     */
    case EXTERNAL_ACTION_UNKNOWN_OPTION = "External action '%s' in %s::actions() sets unknown property %s::\$%s: the "
        . 'class has no public property or setter with that name.';

    /**
     * Error when a `new` configuration value is never accepted by the property or setter Yii assigns it to.
     */
    case NEW_OBJECT_INVALID_OPTION_TYPE = 'new %s() sets $%s to %s, but %s accepts only %s, even with type coercion.';

    /**
     * Error when a `new` configuration option names no public property or setter of the constructed class.
     */
    case NEW_OBJECT_UNKNOWN_OPTION = 'new %s() sets unknown property $%s: the class has no public property or setter '
        . 'with that name.';

    /**
     * Error when an application property is neither native, a component, nor annotation-based.
     */
    case PROPERTY_NOT_RESOLVED = 'Property %s::$%s is neither native, a component, nor annotation-based.';

    /**
     * Error when a constant array shape key has no matching value type.
     */
    case SHAPE_KEY_WITHOUT_VALUE_TYPE = 'Shape key without a value type.';

    /**
     * Error when a service or component definition is unsupported.
     */
    case UNSUPPORTED_DEFINITION = "Unsupported definition for '%s'.";

    /**
     * Error when a validation rule has no non-null attribute names at key `0` or validator type at key `1`.
     */
    case VALIDATOR_INCOMPLETE_RULE = 'Validation rule %s in %s::rules() has %s, which Model::createValidators() '
        . 'rejects: a rule must specify the attribute names at key 0 and the validator type at key 1.';

    /**
     * Error when a configuration value or identifier is not a string.
     */
    case VALUE_NOT_STRING = "'%s': '%s' must be a 'string', got '%s'.";

    /**
     * Error when a `widget()` or `begin()` option value is never accepted by the property or setter Yii assigns it to.
     */
    case WIDGET_INVALID_OPTION_TYPE = '%s::%s() sets %s::$%s to %s, but %s accepts only %s, even with type coercion.';

    /**
     * Error when a `widget()` or `begin()` option names no public property or setter of the created widget.
     */
    case WIDGET_UNKNOWN_OPTION = '%s::%s() sets unknown property %s::$%s: the class has no public property or setter '
        . 'with that name.';

    /**
     * Returns the formatted message string for the error case.
     *
     * @param int|string ...$argument Values to insert into the message template.
     *
     * @return string Formatted error message with interpolated arguments.
     */
    public function getMessage(int|string ...$argument): string
    {
        return sprintf($this->value, ...$argument);
    }
}
