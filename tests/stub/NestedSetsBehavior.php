<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\stub;

use yii\base\Behavior;
use yii\db\ActiveRecord;

/**
 * Provides nested sets functionality for Active Record models via a Yii behavior mechanism.
 *
 * This class defines properties and methods for implementing nested sets model operations on Active Record instances,
 * enabling hierarchical data structure management at runtime. It demonstrates the use of typed properties for tree
 * structure manipulation in Yii Applications.
 *
 * The nested sets model uses three integer properties to represent hierarchical relationships: depth for the node level
 * in the tree, lft for the left boundary, and rgt for the right boundary of the node's subtree.
 *
 * This behavior is specifically designed for testing PHPStan extensions and their ability to handle behavior property
 * definitions, ensuring proper type resolution and property access in Active Record models with attached behaviors.
 *
 * Key features.
 * - Depth property for representing node level in hierarchical structures.
 * - Left and right boundary properties for nested sets model implementation.
 * - Property type definitions for static analysis and IDE autocompletion.
 * - Type-safe integration with Active Record models.
 *
 * @template T of ActiveRecord
 * @extends Behavior<T>
 *
 * @property int $depth
 * @property int $lft
 * @property int $rgt
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class NestedSetsBehavior extends Behavior {}
