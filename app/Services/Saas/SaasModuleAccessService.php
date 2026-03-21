<?php

declare(strict_types=1);

namespace App\Services\Saas;

use App\Contracts\ModuleAccessContract;
use App\Models\User;

/**
 * Delega para {@see User::canAccessSaasModule} (regra única no modelo).
 */
final class SaasModuleAccessService implements ModuleAccessContract
{
    public function can(User $user, string $module): bool
    {
        return $user->canAccessSaasModule($module);
    }
}
