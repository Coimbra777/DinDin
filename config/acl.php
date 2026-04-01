<?php

/**
 * ACL canónica (Gates em {@see App\Providers\AuthServiceProvider}).
 *
 * O armazenamento continua a ser: flag {@code users.is_admin} + pivot {@code saas_module_user}
 * (slugs em {@see App\Models\SaasModule}). Isto documenta os nomes estáveis para policies,
 * testes e código novo — sem nova tabela até ser necessário.
 */
return [
    'gates' => [
        'admin.access' => 'Acesso ao painel /cms/admin e APIs de administração.',
        'saas-module' => 'Um argumento: slug do módulo (ex.: finance, reports, planning, projections).',
        'finance.use' => 'Entrada na área de finanças (equivalente ao slug finance).',
    ],

    /*
    | Mapeamento semântico futuro (roles explícitos). Hoje:
    | - admin → users.is_admin
    | - user  → autenticado, não admin
    */
    'roles' => [
        'admin' => 'admin.access',
        'user' => null,
    ],
];
