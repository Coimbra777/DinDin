<?php

declare(strict_types=1);

use App\Http\Controllers\Finance\Api\ProjectionApiController;
use Illuminate\Support\Facades\Route;

/*
| Projeção — prefixo pai "projection" → /api/projection/*
| Resposta: { months: [{ month, income, expense, expense_card, balance }] } (só dados reais por mês).
*/
Route::get('/', [ProjectionApiController::class, 'show']);
