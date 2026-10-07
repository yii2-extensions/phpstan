<?php

declare(strict_types=1);

use yii\caching\{ArrayCache, CacheInterface, FileCache};
use yii\di\Instance;
use yii\web\View;
use yii2\extensions\phpstan\tests\support\stub\PlainService;

return [
    'components' => [
        ArrayIterator::class => static fn() => new View(),
        'closureView' => static fn(): View => new View(),
        'instanceComponent' => Instance::of('view'),
        'prefixedView' => ['class' => '\yii\web\View'],
        'request' => ['cookieValidationKey' => 'secret'],
        'stringView' => View::class,
        'untypedClosure' => static fn() => new View(),
    ],
    'container' => [
        'definitions' => [
            ArrayObject::class => static fn() => new SplStack(),
            CacheInterface::class => FileCache::class,
            'closureCache' => static fn(): CacheInterface => new ArrayCache(),
            'instanceService' => Instance::of(SplStack::class),
            'mailer.alias' => 'mailer.real',
            'mailer.real' => ['class' => View::class],
            PlainService::class => ['mode' => 'sync'],
            SplObjectStorage::class => 'untypedService',
            'typedService' => static fn(): SplStack => new SplStack(),
            'untypedService' => static fn() => new SplStack(),
            'untypedServiceAlias' => 'untypedService',
        ],
    ],
];
