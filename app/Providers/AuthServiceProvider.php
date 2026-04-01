<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Finance\Category;
use App\Models\Finance\Transaction;
use App\Models\User;
use App\Policies\Finance\CategoryPolicy;
use App\Policies\Finance\TransactionPolicy;
use App\Support\GateNames;
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
        Transaction::class => TransactionPolicy::class,
        Category::class => CategoryPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        /*
         * ACL canónica (ver config/acl.php). Persistência atual: is_admin + saas_module_user.
         * Uso: Gate::allows(\App\Support\GateNames::ADMIN), Gate::allows(\App\Support\GateNames::SAAS_MODULE, 'reports'), etc.
         */
        Gate::define(GateNames::ADMIN, fn (User $user): bool => $user->isAdmin());

        Gate::define(GateNames::SAAS_MODULE, fn (User $user, string $slug): bool => $user->canAccessSaasModule($slug));

        Gate::define(GateNames::FINANCE, fn (User $user): bool => $user->canAccessSaasModule('finance'));
    }
}
