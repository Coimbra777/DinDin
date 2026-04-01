<?php

/*
|--------------------------------------------------------------------------
| CMS / app autenticada — autenticação + finanças (sem CMS institucional antigo)
|--------------------------------------------------------------------------
|
| API de finanças (canónica): /cms/finance/api/* — fragmentos em routes/finance-api/*.php
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Api\TinyMceController;
use App\Http\Controllers\Cms\Admin\AdminSpaController;
use App\Http\Controllers\Cms\Admin\UserAdminApiController;
use App\Http\Controllers\Cms\Admin\UserAdminController;
use App\Http\Controllers\Cms\Auth\ForgotPasswordController;
use App\Http\Controllers\Cms\Auth\LoginController;
use App\Http\Controllers\Cms\Auth\RegisterController;
use App\Http\Controllers\Cms\Auth\ResetPasswordController;
use App\Http\Controllers\Finance\Api\CategoryApiController;
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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect('/cms/dashboard');
    }

    return redirect('/cms/login');
});

Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login'])->middleware('throttle:login');

Route::get('register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('register', [RegisterController::class, 'register'])->middleware('throttle:register');

Route::get('password/forgot', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('cms.password.forgot');
Route::post('forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->middleware('throttle:forgot-password')->name('cms.forgot-password');
Route::get('password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('reset-password', [ResetPasswordController::class, 'reset'])->middleware('throttle:reset-password')->name('cms.reset-password');

Route::middleware(['auth'])->group(function () {
    Route::post('api/upload', [TinyMceController::class, 'uploadImage'])->name('cms.api.tinymce.upload');
    Route::post('api/remove_media', [TinyMceController::class, 'removeImage'])->name('cms.api.tinymce.remove');

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

    Route::middleware(['finance.module'])->prefix('finance')->group(function () {
        Route::prefix('api')->group(function () {
            Route::get('dashboard', [DashboardApiController::class, 'show'])->name('finance.api.dashboard');
            Route::get('dashboard/upcoming', [DashboardApiController::class, 'upcoming'])->name('finance.api.dashboard.upcoming');
            Route::get('user/onboarding', [FinanceOnboardingApiController::class, 'show'])->name('finance.api.user.onboarding');
            Route::post('user/onboarding/complete', [FinanceOnboardingApiController::class, 'complete'])->name('finance.api.user.onboarding.complete');
            Route::get('projection', [ProjectionApiController::class, 'show'])
                ->middleware('module:projections')
                ->name('finance.api.projection');
            Route::get('summary', [SummaryApiController::class, 'show'])->name('finance.api.summary');
            Route::get('transactions', [TransactionApiController::class, 'index'])->name('finance.api.transactions');
            Route::get('transactions/recent', [TransactionApiController::class, 'recent'])->name('finance.api.transactions.recent');
            Route::get('categories', [CategoryApiController::class, 'index'])->name('finance.api.categories');
            Route::post('categories', [CategoryApiController::class, 'store'])->middleware('throttle:finance-api-mutations')->name('finance.api.categories.store');
            Route::put('categories/{category}', [CategoryApiController::class, 'update'])->middleware('throttle:finance-api-mutations')->name('finance.api.categories.update');
            Route::delete('categories/{category}', [CategoryApiController::class, 'destroy'])->middleware('throttle:finance-api-mutations')->name('finance.api.categories.destroy');
            Route::post('transactions', [TransactionApiController::class, 'store'])->middleware('throttle:finance-api-mutations')->name('finance.api.transactions.store');
            Route::post('transactions/{transaction}/mark-as-paid', [TransactionApiController::class, 'markAsPaid'])->middleware('throttle:finance-api-mutations')->name('finance.api.transactions.mark_as_paid');
            Route::post('transactions/{transaction}/duplicate', [TransactionApiController::class, 'duplicate'])->middleware('throttle:finance-api-mutations')->name('finance.api.transactions.duplicate');
            Route::put('transactions/{transaction}', [TransactionApiController::class, 'update'])->middleware('throttle:finance-api-mutations')->name('finance.api.transactions.update');
            Route::delete('transactions/{transaction}', [TransactionApiController::class, 'destroy'])->middleware('throttle:finance-api-mutations')->name('finance.api.transactions.destroy');
            Route::middleware('module:reports')->group(function () {
                Route::get('reports/categories', [ReportApiController::class, 'categories'])->name('finance.api.reports.categories');
                Route::get('reports/trend', [ReportApiController::class, 'trend'])->name('finance.api.reports.trend');
            });
            Route::prefix('goals')->group(function () {
                require base_path('routes/finance-api/goals.php');
            });
            Route::prefix('alerts')->group(function () {
                require base_path('routes/finance-api/alerts.php');
            });
            Route::prefix('insights')->group(function () {
                require base_path('routes/finance-api/insights.php');
            });
            Route::prefix('credit-simulator')->group(function () {
                require base_path('routes/finance-api/credit-simulator.php');
            });
            Route::prefix('planning')->middleware('module:planning')->group(function () {
                require base_path('routes/finance-api/planning.php');
            });
        });

        Route::post('onboarding/complete', [FinanceOnboardingWebController::class, 'complete'])
            ->name('finance.onboarding.complete');

        Route::get('finance_dashboard', [FinanceDashboardController::class, 'index'])
            ->name('finance_dashboard.index');

        /*
        | Blade CRUD legado (views em resources/views/cms/finance/*) — a UI canónica é a SPA Vue.
        | Mantido para bookmarks/POST antigos; novas funcionalidades devem ir para a API em finance/api/*.
        */
        Route::resource('finance_transactions', TransactionController::class)->except(['show']);
        Route::resource('finance_categories', CategoryController::class)->except(['show']);
    });

    Route::post('logout', [LoginController::class, 'logout'])->name('logout');
});
