<?php

declare(strict_types=1);

namespace yii2\extensions\phpstan\tests\stub;

use yii\base\Behavior;
use yii\db\ActiveRecord;

/**
 * Provides URL-friendly slug generation functionality for Active Record models via a Yii behavior mechanism.
 *
 * This class defines a slug property to be attached to Active Record components, enabling automatic generation of
 * URL-friendly identifiers at runtime. It demonstrates the use of typed properties for string-based identifier
 * management in Yii Applications.
 *
 * The slug property is designed to store URL-safe string representations of model data, typically generated from
 * title, name, or other human-readable fields to create SEO-friendly URLs and consistent identifier patterns.
 *
 * This behavior is specifically designed for testing PHPStan extensions and their ability to handle behavior property
 * definitions, ensuring proper type resolution and property access in Active Record models with attached behaviors.
 *
 * Key features.
 * - Property type definitions for static analysis and IDE autocompletion.
 * - Slug property for URL-friendly identifier generation.
 * - Type-safe integration with Active Record models.
 *
 * @template T of ActiveRecord
 * @extends Behavior<T>
 *
 * @property string $slug
 *
 * @copyright Copyright (C) 2023 Terabytesoftw.
 * @license https://opensource.org/license/bsd-3-clause BSD 3-Clause License.
 */
final class SlugBehavior extends Behavior {}
