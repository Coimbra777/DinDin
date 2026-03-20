<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Origens permitidas (CORS)
    |--------------------------------------------------------------------------
    |
    | Lista separada por vírgulas. Se vazio, usa APP_URL como origem única.
    | Ex.: https://app.exemplo.com,https://admin.exemplo.com
    |
    */

    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))
    ))),

];
