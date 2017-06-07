<?php
return [
    'trustedProxies' => array_merge([
        '127.0.0.1',
        '::1',
    ], preg_split('/[,; ]+/',env('TRUSTED_PROXIES'))),
];