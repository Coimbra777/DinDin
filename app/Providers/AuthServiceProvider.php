<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        /*
         * ACL canónica (ver config/acl.php). Persistência atual: is_admin + saas_module_user.
         * Uso: Gate::allows('admin.access'), Gate::allows('saas-module', 'reports'), etc.
         */
        Gate::define('admin.access', fn (User $user): bool => $user->isAdmin());

        Gate::define('saas-module', fn (User $user, string $slug): bool => $user->canAccessSaasModule($slug));

        Gate::define('finance.use', fn (User $user): bool => $user->canAccessSaasModule('finance'));
    }
}
