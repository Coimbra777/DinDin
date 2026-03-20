<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Api\TinyMceController;
use App\Http\Controllers\AuthController;
use App\Modules\CreditCard\Http\Controllers\Api\CreditCardApiController;
use App\Modules\Finance\Http\Controllers\Api\DashboardApiController;
use App\Modules\Projection\Http\Controllers\Api\ProjectionApiController;
use Illuminate\Support\Facades\Route;

Route::middleware('cors')->group(function () {
    Route::get('/', function () {
        return response(['API' => 'Works'], 200);
    });

    Route::post('/upload', [TinyMceController::class, 'uploadImage']);
    Route::post('/remove_media', [TinyMceController::class, 'removeImage']);
});

Route::group([
    'middleware' => 'api',
    'prefix' => 'auth',
], function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('me', [AuthController::class, 'me']);
    Route::post('register', [AuthController::class, 'register']);
});

/*
| Módulos financeiros — sessão CMS (`web` + `auth`).
| /api/finance/* /api/cards/* /api/projection/* /api/reports/*
*/
Route::middleware(['web', 'auth'])->group(function () {
    Route::prefix('finance')->group(function () {
        require base_path('app/Modules/Finance/Routes/api.php');
    });
    Route::prefix('cards')->group(function () {
        require base_path('app/Modules/CreditCard/Routes/api.php');
    });
    Route::prefix('projection')->group(function () {
        require base_path('app/Modules/Projection/Routes/api.php');
    });
    Route::prefix('reports')->group(function () {
        require base_path('app/Modules/Reports/Routes/api.php');
    });

    /** @deprecated use GET /api/finance/dashboard */
    Route::get('/dashboard', [DashboardApiController::class, 'show']);
    /** @deprecated use GET /api/projection */
    Route::get('/projection', [ProjectionApiController::class, 'show']);
    /** @deprecated use rotas em /api/cards */
    Route::get('/credit-cards', [CreditCardApiController::class, 'index']);
    Route::post('/credit-cards', [CreditCardApiController::class, 'store']);
    Route::put('/credit-cards/{finance_credit_card}', [CreditCardApiController::class, 'update']);
    Route::delete('/credit-cards/{finance_credit_card}', [CreditCardApiController::class, 'destroy']);
    Route::get('/credit-cards/{finance_credit_card}/bill', [CreditCardApiController::class, 'bill']);
});
