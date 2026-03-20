<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
 */

Route::middleware('cors')->group(function () {
    Route::get('/', function () {
        return response(['API' => 'Works'], 200);
    });

    Route::post('/upload', 'Api\TinyMceController@uploadImage');
    Route::post('/remove_media', 'Api\TinyMceController@removeImage');
});

Route::group([
    'middleware' => 'api',
    'prefix' => 'auth',
], function () {
    Route::post('login', 'AuthController@login');
    Route::post('logout', 'AuthController@logout');
    Route::post('me', 'AuthController@me');
    Route::post('register', 'AuthController@register');
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
    Route::get('/dashboard', [\App\Modules\Finance\Http\Controllers\Api\DashboardApiController::class, 'show']);
    /** @deprecated use GET /api/projection */
    Route::get('/projection', [\App\Modules\Projection\Http\Controllers\Api\ProjectionApiController::class, 'show']);
    /** @deprecated use rotas em /api/cards */
    Route::get('/credit-cards', [\App\Modules\CreditCard\Http\Controllers\Api\CreditCardApiController::class, 'index']);
    Route::post('/credit-cards', [\App\Modules\CreditCard\Http\Controllers\Api\CreditCardApiController::class, 'store']);
    Route::put('/credit-cards/{finance_credit_card}', [\App\Modules\CreditCard\Http\Controllers\Api\CreditCardApiController::class, 'update']);
    Route::delete('/credit-cards/{finance_credit_card}', [\App\Modules\CreditCard\Http\Controllers\Api\CreditCardApiController::class, 'destroy']);
    Route::get('/credit-cards/{finance_credit_card}/bill', [\App\Modules\CreditCard\Http\Controllers\Api\CreditCardApiController::class, 'bill']);
});
