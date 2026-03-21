<?php

/*
|--------------------------------------------------------------------------
| CMS / app autenticada — autenticação + finanças (sem CMS institucional antigo)
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Cms\Auth\ForgotPasswordController;
use App\Http\Controllers\Cms\Auth\LoginController;
use App\Http\Controllers\Cms\Auth\RegisterController;
use App\Http\Controllers\Cms\Auth\ResetPasswordController;
use App\Http\Controllers\Finance\Api\CategoryApiController;
use App\Http\Controllers\Finance\Api\CreditCardApiController;
use App\Http\Controllers\Finance\Api\DashboardApiController;
use App\Http\Controllers\Finance\Api\FinanceOnboardingApiController;
use App\Http\Controllers\Finance\Api\ProjectionApiController;
use App\Http\Controllers\Finance\Api\ReportApiController;
use App\Http\Controllers\Finance\Api\SummaryApiController;
use App\Http\Controllers\Finance\Api\TransactionApiController;
use App\Http\Controllers\Finance\CategoryController;
use App\Http\Controllers\Finance\FinanceDashboardController;
use App\Http\Controllers\Finance\FinanceOnboardingWebController;
use App\Http\Controllers\Finance\TransactionController;
use App\Http\Controllers\Cms\Admin\AdminSpaController;
use App\Http\Controllers\Cms\Admin\UserAdminApiController;
use App\Http\Controllers\Cms\Admin\UserAdminController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect('/cms/dashboard');
    }

    return redirect('/cms/login');
});

Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login']);

Route::get('register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('register', [RegisterController::class, 'register']);

Route::get('password/forgot', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('cms.password.forgot');
Route::post('forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('cms.forgot-password');
Route::get('password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('reset-password', [ResetPasswordController::class, 'reset'])->name('cms.reset-password');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', function () {
        return redirect()->route('finance_dashboard.index');
    })->name('dashboard.index');

    Route::middleware(['admin'])->prefix('admin')->name('cms.admin.')->group(function () {
        Route::get('/', AdminSpaController::class)->name('home');

        Route::prefix('api')->name('api.')->group(function () {
            Route::get('users', [UserAdminApiController::class, 'index'])->name('users.index');
            Route::get('users/{user}', [UserAdminApiController::class, 'show'])->name('users.show');
            Route::post('users/{user}/modules', [UserAdminApiController::class, 'updateModules'])->name('users.modules');
        });

        Route::get('users', [UserAdminController::class, 'index'])->name('users.index');
        Route::get('users/{user}/edit', [UserAdminController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [UserAdminController::class, 'update'])->name('users.update');
    });

    Route::middleware(['module:finance'])->prefix('finance')->group(function () {
        Route::prefix('api')->group(function () {
            Route::get('dashboard', [DashboardApiController::class, 'show'])->name('finance.api.dashboard');
            Route::get('user/onboarding', [FinanceOnboardingApiController::class, 'show'])->name('finance.api.user.onboarding');
            Route::post('user/onboarding/complete', [FinanceOnboardingApiController::class, 'complete'])->name('finance.api.user.onboarding.complete');
            Route::get('projection', [ProjectionApiController::class, 'show'])->name('finance.api.projection');
            Route::get('summary', [SummaryApiController::class, 'show'])->name('finance.api.summary');
            Route::get('transactions', [TransactionApiController::class, 'index'])->name('finance.api.transactions');
            Route::get('transactions/recent', [TransactionApiController::class, 'recent'])->name('finance.api.transactions.recent');
            Route::get('categories', [CategoryApiController::class, 'index'])->name('finance.api.categories');
            Route::post('categories', [CategoryApiController::class, 'store'])->name('finance.api.categories.store');
            Route::put('categories/{category}', [CategoryApiController::class, 'update'])->name('finance.api.categories.update');
            Route::delete('categories/{category}', [CategoryApiController::class, 'destroy'])->name('finance.api.categories.destroy');
            Route::post('transactions', [TransactionApiController::class, 'store'])->name('finance.api.transactions.store');
            Route::put('transactions/{transaction}', [TransactionApiController::class, 'update'])->name('finance.api.transactions.update');
            Route::delete('transactions/{transaction}', [TransactionApiController::class, 'destroy'])->name('finance.api.transactions.destroy');
            Route::get('credit-cards', [CreditCardApiController::class, 'index'])->name('finance.api.credit-cards.index');
            Route::post('credit-cards', [CreditCardApiController::class, 'store'])->name('finance.api.credit-cards.store');
            Route::put('credit-cards/{finance_credit_card}', [CreditCardApiController::class, 'update'])->name('finance.api.credit-cards.update');
            Route::delete('credit-cards/{finance_credit_card}', [CreditCardApiController::class, 'destroy'])->name('finance.api.credit-cards.destroy');
            Route::get('credit-cards/{finance_credit_card}/bill', [CreditCardApiController::class, 'bill'])->name('finance.api.credit-cards.bill');
            Route::get('reports/categories', [ReportApiController::class, 'categories'])->name('finance.api.reports.categories');
            Route::get('reports/trend', [ReportApiController::class, 'trend'])->name('finance.api.reports.trend');
            Route::prefix('goals')->group(function () {
                require base_path('routes/api/goals.php');
            });
            Route::prefix('alerts')->group(function () {
                require base_path('routes/api/alerts.php');
            });
            Route::prefix('insights')->group(function () {
                require base_path('routes/api/insights.php');
            });
            Route::prefix('credit-simulator')->group(function () {
                require base_path('routes/api/credit-simulator.php');
            });
            Route::prefix('planning')->group(function () {
                require base_path('routes/api/planning.php');
            });
        });

        Route::post('onboarding/complete', [FinanceOnboardingWebController::class, 'complete'])
            ->name('finance.onboarding.complete');

        Route::get('finance_dashboard', [FinanceDashboardController::class, 'index'])
            ->name('finance_dashboard.index');
        Route::resource('finance_transactions', TransactionController::class)->except(['show']);
        Route::resource('finance_categories', CategoryController::class)->except(['show']);
    });

    Route::post('logout', [LoginController::class, 'logout'])->name('logout');
});
