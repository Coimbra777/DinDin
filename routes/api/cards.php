<?php

declare(strict_types=1);

use App\Http\Controllers\Finance\Api\CreditCardApiController;
use Illuminate\Support\Facades\Route;

/*
| Cartões, faturas e limite — registado com prefixo pai "cards" → /api/cards/*
*/
Route::get('/', [CreditCardApiController::class, 'index']);
Route::post('/', [CreditCardApiController::class, 'store']);
Route::put('/{finance_credit_card}', [CreditCardApiController::class, 'update']);
Route::delete('/{finance_credit_card}', [CreditCardApiController::class, 'destroy']);
Route::get('/{finance_credit_card}/bill', [CreditCardApiController::class, 'bill']);
