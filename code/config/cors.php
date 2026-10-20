<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 跨源资源共享（CORS）配置
    |--------------------------------------------------------------------------
    |
    | 您可以在这里配置跨源资源共享的设置或“CORS”。
    | 这决定了可以在浏览器中执行哪些跨域操作.
    | 您可以根据需要自由调整这些设置。
    |
    | 了解更多信息: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['Authorization'],

    'max_age' => 0,

    'supports_credentials' => false,

];
