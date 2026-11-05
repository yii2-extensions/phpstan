<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\rules;

use yii\base\Controller;

/**
 * Represents an external action definition of {@see Controller::actions()} whose class and assignment path are proven.
 *
 * {@see ExternalActionConfigurationResolver} for the conditions under which a definition is resolved.
 */
final readonly class ExternalActionConfiguration
{
    /**
     * @param string $controllerClass Controller whose `actions()` returns the definition, as PHPStan displays it.
     * @param string $actionId Action ID that maps to the definition.
     * @param CreateObjectConfiguration $configuration Configuration that `Yii::createObject()` receives from
     * {@see Controller::createAction()}.
     */
    public function __construct(
        public string $controllerClass,
        public string $actionId,
        public CreateObjectConfiguration $configuration,
    ) {}
}
