<?php

return [
    /*
    |--------------------------------------------------------------------------
    | External Mail API (opcional)
    |--------------------------------------------------------------------------
    |
    | Configuração para APIs externas de envio de e-mail.
    | Use quando precisar de serviços como automação de marketing.
    | Deixe vazio para usar apenas o Laravel Mail nativo.
    |
    */

    'enabled' => env('MAIL_API_ENABLED', false),

    'url' => env('MAIL_API_URL'), // URL completa do endpoint de automação
    'base_url' => env('MAIL_API_BASE_URL'), // Base para arquivo (ex: https://api.example.com/email/)
    'authenticator' => env('MAIL_API_AUTHENTICATOR'),
    'password' => env('MAIL_API_PASSWORD'),
    'from_address' => env('MAIL_API_FROM_ADDRESS', config('mail.from.address')),
    'from_name' => env('MAIL_API_FROM_NAME', config('mail.from.name')),
    'default_recipient' => env('MAIL_API_DEFAULT_RECIPIENT'),

    /*
    |--------------------------------------------------------------------------
    | CRM/Marketing API (opcional)
    |--------------------------------------------------------------------------
    |
    | Para integração com CRMs ou ferramentas de marketing.
    |
    */
    'crm_url' => env('MAIL_CRM_API_URL'),
    'crm_auth' => env('MAIL_CRM_API_AUTH'),
];
