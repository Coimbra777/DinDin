# Multi‑usuário (dados financeiros)

Cada utilizador autenticado só acede aos seus registos:

- **Queries:** modelos em `App\Models\Finance\*` usam `scopeForUser($userId)` ou equivalente.
- **Rotas API:** parâmetros como `finance_goal`, `finance_monthly_plan` são resolvidos em `RouteServiceProvider` com `where('user_id', auth()->id())`.
- **Controllers:** recebem `user_id` apenas de `$request->user()->id` (ou helper `App\Services\Finance\FinanceTenant::id($request)`).

Não foi aplicado *global scope* automático em todos os modelos (evita efeitos em comandos Artisan / jobs sem utilizador). Novas funcionalidades devem repetir o mesmo padrão explícito.
