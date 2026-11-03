<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\support\createobject;

use yii\base\Component;

/**
 * Component target that implements {@see ComponentMappedContract}.
 */
final class ComponentContractTarget extends Component implements ComponentMappedContract {}
