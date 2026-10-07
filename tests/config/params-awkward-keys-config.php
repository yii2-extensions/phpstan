<?php

declare(strict_types=1);

return [
    'params' => [
        "it's" => 'a',
        'back\\slash' => 'b',
        'quote"d' => 1,
        '' => 'empty',
        ' ' => 1.5,
        'a b' => true,
        "new\nline" => 2,
        "tab\tkey" => null,
        'x*/y' => 3,
        'a$b' => 'dollar',
        "c\x01" => 4,
        'é' => 'utf8',
        -5 => 'negative',
        '123' => 'numeric',
        'nested' => ['*/' => 5, "line\nbreak" => ['ok' => false]],
        'list' => ['a', 'b'],
    ],
];
