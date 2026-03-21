<?php

declare(strict_types=1);

return [
    /*
    | Email do primeiro administrador (apenas na migração inicial).
    | Ver database/migrations/*_add_is_admin_and_saas_modules.php
    */
    'bootstrap_admin_email' => env('SAAS_BOOTSTRAP_ADMIN_EMAIL'),

    /*
    | Futuro: módulos por plano (free, pro, …).
    | Ex.: 'plans' => [ 'free' => ['modules' => []], 'pro' => ['modules' => ['finance']] ]
    */
    'plans' => [],
];
