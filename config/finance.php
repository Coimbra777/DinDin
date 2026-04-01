<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Interface só finanças
    |--------------------------------------------------------------------------
    | Quando true, telas finanças usam layout sem menu AdminLTE do CMS.
    */
    'standalone_ui' => env('FINANCE_STANDALONE_UI', true),

    /*
    | POST/PUT/DELETE em transações e categorias (API). Em testes use valor alto
    | no phpunit.xml ou FINANCE_API_MUTATIONS_PER_MINUTE para não falsificar 429.
    */
    'api_mutations_per_minute' => (int) env('FINANCE_API_MUTATIONS_PER_MINUTE', 60),

];
