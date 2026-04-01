<?php

namespace App\Providers;

use App\Models\Finance\Category;
use App\Models\Finance\FinanceGoal;
use App\Models\Finance\FinanceMonthlyPlan;
use App\Models\Finance\Transaction;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        /*
         * API JSON (parâmetros {transaction} e {category} só são usados em /cms/finance/api/*).
         * Garante que implicit binding não expõe IDOR (resolveModel sem scope de user).
         */
        Route::bind('transaction', function ($value) {
            if (! auth()->check()) {
                abort(403);
            }

            return Transaction::query()
                ->where('id', $value)
                ->where('user_id', auth()->id())
                ->firstOrFail();
        });

        Route::bind('category', function ($value) {
            if (! auth()->check()) {
                abort(403);
            }

            return Category::query()
                ->where('id', $value)
                ->where('user_id', auth()->id())
                ->firstOrFail();
        });

        Route::bind('finance_transaction', function ($value) {
            if (! auth()->check()) {
                abort(403);
            }

            return Transaction::query()
                ->where('id', $value)
                ->where('user_id', auth()->id())
                ->firstOrFail();
        });

        Route::bind('finance_category', function ($value) {
            if (! auth()->check()) {
                abort(403);
            }

            return Category::query()
                ->where('id', $value)
                ->where('user_id', auth()->id())
                ->firstOrFail();
        });

        Route::bind('finance_goal', function ($value) {
            if (! auth()->check()) {
                abort(403);
            }

            return FinanceGoal::query()
                ->where('id', $value)
                ->where('user_id', auth()->id())
                ->firstOrFail();
        });

        Route::bind('finance_monthly_plan', function ($value) {
            if (! auth()->check()) {
                abort(403);
            }

            return FinanceMonthlyPlan::query()
                ->where('id', $value)
                ->where('user_id', auth()->id())
                ->firstOrFail();
        });

        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapWebRoutes();

        $this->mapCmsRoutes();
    }

    /**
     * Define the "web" routes for your application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
            ->group(base_path('routes/front.php'));
    }

    /**
     * Define the "cms" routes for your application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapCmsRoutes()
    {
        // Sem ->namespace(): todas as rotas usam FQCN ([SomeController::class, 'method']).
        Route::prefix('cms')
            ->middleware('cms')
            ->group(base_path('routes/cms.php'));
    }
}
