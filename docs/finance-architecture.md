# Finanças pessoais — organização do código

## API canónica (SPA Vue)

A aplicação montada em `#finance-app` usa **exclusivamente** o prefixo:

**`/cms/finance/api/*`** (definido em `routes/cms.php`, middleware `web` + `auth` + `finance.module`).

## Espelho deprecado em `/api/*`

`routes/api.php` regista as mesmas capacidades financeiras sob `/api/...` (ex.: `/api/finance/...`, `/api/goals`, …) com middleware `deprecated.finance.api.mirror`, que escreve **`Log::warning`** em cada pedido. Serve para integrações antigas; o objetivo é remover este grupo quando não houver clientes.

---

O domínio financeiro vive sob namespaces Laravel padrão:

| Camada | Local |
|--------|--------|
| Models | `app/Models/Finance/` (`Category`, `Transaction`, `FinanceGoal`, `FinanceMonthlyPlan`, …) |
| Services | `app/Services/Finance/` |
| Controllers web | `app/Http/Controllers/Finance/` |
| Controllers API | `app/Http/Controllers/Finance/Api/` |
| Rotas API (fragmentos) | `routes/api/finance.php`, `projection.php`, `reports.php`, `goals.php`, `alerts.php`, `insights.php`, `credit-simulator.php`, `planning.php` |

Form requests: `app/Http/Requests/Goals/`, `app/Http/Requests/Finance/`.

### Endpoints resumo (espelhados em `/cms/finance/api/...` e `/api/...`)

| Prefixo | Função |
|---------|--------|
| `alerts` | GET — alertas |
| `insights` | GET — análises |
| `credit-simulator` | POST `simulate` — simula parcelas |
| `planning` | CRUD — planeamento mensal (`finance_monthly_plans`) |
| `goals` | CRUD — metas (`finance_goals`) |
| `projection` | GET — JSON `{ months[] }`: cada mês futuro com totais reais (`aggregateMonthStats`, igual a `GET /transactions?month=`) e saldo acumulado (receitas − despesas) desde o fim do mês atual |

Ver **`docs/finance-multi-user.md`** para isolamento por `user_id`.

### Autenticação

- **CMS e SPA finanças:** guard `web` (sessão Laravel). Identidade única: modelo `User` / tabela `users`.
- **API espelhada em `/api/*`:** também `web` + `auth` (mesma sessão que o CMS), não há token JWT.
- **Integrações externas headless:** usar [Laravel Sanctum](https://laravel.com/docs/sanctum) (tokens pessoais ou SPA cookie) ou `session` com login web — não está instalado por defeito neste repositório.

### UI legado Blade

Rotas `finance_transactions` e `finance_categories` (resource) em `routes/cms.php` apontam para views Blade; a UI principal é a SPA.
