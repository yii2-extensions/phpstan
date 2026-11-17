<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\rules;

use yii\validators\Validator;

/**
 * Represents the configuration of a `Model::rules()` entry whose created {@see Validator} and assignment path are
 * proven.
 *
 * {@see ValidatorConfigurationResolver} for the conditions under which a configuration is resolved.
 */
final readonly class ValidatorConfiguration
{
    /**
     * Stores the selected class ID, the resolved configuration and the options the rule writes.
     *
     * @param string $id Container ID or class name that `Yii::createObject()` receives as the class of the validator.
     * @param CreateObjectConfiguration $configuration Validator class Yii creates and the options of the call that it
     * assigns: alias defaults, written options and the options {@see Validator::createValidator()} sets.
     * @param list<string> $writtenOptions Names of the options written in the rule after position `1`, in rule order.
     */
    public function __construct(
        public string $id,
        public CreateObjectConfiguration $configuration,
        public array $writtenOptions,
    ) {}
}
