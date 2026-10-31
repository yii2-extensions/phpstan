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
     * Error when no attached behavior provides the requested method.
     */
    case BEHAVIOR_METHOD_NOT_FOUND = 'Method %s::%s() is not provided by any behavior attached to the class.';

    /**
     * Error when no attached behavior provides the requested property.
     */
    case BEHAVIOR_PROPERTY_NOT_FOUND = 'Property %s::$%s is not provided by any behavior attached to the class.';

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
     * Error when a configuration value or identifier is not a string.
     */
    case VALUE_NOT_STRING = "'%s': '%s' must be a 'string', got '%s'.";

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
