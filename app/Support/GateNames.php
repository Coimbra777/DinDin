<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Nomes canónicos dos Gates (evitar strings dispersas).
 *
 * @see \App\Providers\AuthServiceProvider
 */
final class GateNames
{
    public const ADMIN = 'admin.access';

    public const FINANCE = 'finance.use';

    /** Gate com argumento: slug do módulo SaaS (ex. reports, planning). */
    public const SAAS_MODULE = 'saas-module';
}
