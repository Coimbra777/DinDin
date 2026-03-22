# Finanças pessoais — organização do código

## API canónica (SPA Vue)

A aplicação montada em `#finance-app` usa **exclusivamente** o prefixo:

**`/cms/finance/api/*`** (definido em `routes/cms.php`, middleware `web` + `auth` + `finance.module`).

Fragmentos de rotas reutilizáveis: **`routes/finance-api/*.php`** (incluídos sob `/cms/finance/api/...`).

### Breaking change (remoção do espelho `/api/*`)

Foram removidos o ficheiro `routes/api.php`, o prefixo público **`/api/*`** para finanças (ex.: `/api/finance/...`, `/api/goals`, `/api/reports/...`, `/api/projection`, …) e o middleware `deprecated.finance.api.mirror`. Qualquer cliente externo, bookmark ou script que ainda chame esses URLs deve passar a usar **`/cms/finance/api/...`** (mesma sessão e cookies do CMS).

Upload/remoção de imagens **TinyMCE** no backoffice passou de `/api/upload` e `/api/remove_media` para **`/cms/api/upload`** e **`/cms/api/remove_media`** (autenticado).

---

O domínio financeiro vive sob namespaces Laravel padrão:

| Camada | Local |
|--------|--------|
| Models | `app/Models/Finance/` (`Category`, `Transaction`, `FinanceGoal`, `FinanceMonthlyPlan`, …) |
| Services | `app/Services/Finance/` |
| Controllers web | `app/Http/Controllers/Finance/` |
| Controllers API | `app/Http/Controllers/Finance/Api/` |
| Rotas API (fragmentos) | `routes/finance-api/` (`goals.php`, `alerts.php`, `insights.php`, `credit-simulator.php`, `planning.php`); restantes (dashboard, transações, categorias, `projection`, `reports/*`, onboarding) em `routes/cms.php` |

Form requests: `app/Http/Requests/Goals/`, `app/Http/Requests/Finance/`.

### Endpoints resumo (sob `/cms/finance/api/...`)

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
- **Integrações externas headless:** usar [Laravel Sanctum](https://laravel.com/docs/sanctum) (tokens pessoais ou SPA cookie) ou `session` com login web — não está instalado por defeito neste repositório.

### UI legado Blade

Rotas `finance_transactions` e `finance_categories` (resource) em `routes/cms.php` apontam para views Blade; a UI principal é a SPA.
