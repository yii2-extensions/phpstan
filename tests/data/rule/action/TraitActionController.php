<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\data\rule\action;

use yii\base\Controller;

/**
 * Controller whose `actions()` comes from a trait.
 */
final class TraitActionController extends Controller
{
    use ActionsTrait;
}
