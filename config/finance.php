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
    |--------------------------------------------------------------------------
    | Redirecionar dashboard do CMS
    |--------------------------------------------------------------------------
    | Envia /cms/dashboard para o dashboard financeiro.
    */
    'redirect_cms_dashboard_to_finance' => env('FINANCE_REDIRECT_DASHBOARD', true),
];
