<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\User;

interface ModuleAccessContract
{
    /**
     * Indica se o utilizador tem acesso ao módulo SaaS identificado por $module (slug).
     */
    public function can(User $user, string $module): bool;
}
