<?php

declare(strict_types=1);

use yii\caching\{ArrayCache, FileCache};

return [
    'container' => [
        'singletons' => [
            'definitionClassOverride' => FileCache::class,
            'definitionClosureOverride' => FileCache::class,
            'definitionResolvedOverride' => static fn() => new ArrayCache(),
        ],
        'definitions' => [
            'definitionClassOverride' => ArrayCache::class,
            'definitionClosureOverride' => static fn() => new ArrayCache(),
            'definitionResolvedOverride' => FileCache::class,
        ],
    ],
];
