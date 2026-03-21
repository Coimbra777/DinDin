<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Duplicação intencional (fonte canónica para a app Vue: /cms/finance/api/*):
| os prefixos abaixo espelham finanças sob /api/* para compatibilidade.
| @deprecated Preferir rotas em routes/cms.php (prefixo cms/finance/api) quando possível.
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Api\TinyMceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Finance\Api\CreditCardApiController;
use App\Http\Controllers\Finance\Api\DashboardApiController;
use App\Http\Controllers\Finance\Api\ProjectionApiController;
use Illuminate\Support\Facades\Route;

Route::middleware('cors')->group(function () {
    Route::get('/', function () {
        return response(['API' => 'Works'], 200);
    });
});

Route::middleware(['cors', 'web', 'auth'])->group(function () {
    Route::post('/upload', [TinyMceController::class, 'uploadImage'])->name('api.tinymce.upload');
    Route::post('/remove_media', [TinyMceController::class, 'removeImage'])->name('api.tinymce.remove');
});

Route::group([
    'middleware' => 'api',
    'prefix' => 'auth',
], function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('me', [AuthController::class, 'me']);
    if (config('auth.allow_jwt_registration')) {
        Route::post('register', [AuthController::class, 'register']);
    }
});

/*
| Módulos financeiros — sessão CMS (`web` + `auth` + permissão por slug SaaS).
*/
Route::middleware(['web', 'auth'])->group(function () {
    Route::middleware('finance.module')->group(function () {
        Route::prefix('finance')->group(function () {
            require base_path('routes/api/finance.php');
        });
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

        /** @deprecated use GET /api/finance/dashboard */
        Route::get('/dashboard', [DashboardApiController::class, 'show']);
    });

    Route::middleware('module:cards')->group(function () {
        Route::prefix('cards')->group(function () {
            require base_path('routes/api/cards.php');
        });
        /** @deprecated use rotas em /api/cards */
        Route::get('/credit-cards', [CreditCardApiController::class, 'index']);
        Route::post('/credit-cards', [CreditCardApiController::class, 'store']);
        Route::put('/credit-cards/{finance_credit_card}', [CreditCardApiController::class, 'update']);
        Route::delete('/credit-cards/{finance_credit_card}', [CreditCardApiController::class, 'destroy']);
        Route::get('/credit-cards/{finance_credit_card}/bill', [CreditCardApiController::class, 'bill']);
    });

    Route::middleware('module:projections')->group(function () {
        Route::prefix('projection')->group(function () {
            require base_path('routes/api/projection.php');
        });
        /** @deprecated use GET /api/projection */
        Route::get('/projection', [ProjectionApiController::class, 'show']);
    });

    Route::middleware('module:reports')->group(function () {
        Route::prefix('reports')->group(function () {
            require base_path('routes/api/reports.php');
        });
    });

    Route::middleware('module:planning')->group(function () {
        Route::prefix('planning')->group(function () {
            require base_path('routes/api/planning.php');
        });
    });
});
