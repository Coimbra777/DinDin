<?php

declare(strict_types=1);

namespace App\Services\Finance;

use Illuminate\Http\Request;

/**
 * Identificador do utilizador autenticado nas APIs financeiras (isolamento multi‑usuário).
 */
final class FinanceTenant
{
    public static function id(Request $request): int
    {
        return (int) $request->user()->id;
    }
}
