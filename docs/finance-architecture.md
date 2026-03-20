# Finanças pessoais — organização do código

O domínio financeiro vive sob namespaces Laravel padrão:

| Camada | Local |
|--------|--------|
| Models | `app/Models/Finance/` (`Category`, `Transaction`, `CreditCard`, `FinanceGoal`, `FinanceMonthlyPlan`) |
| Services | `app/Services/Finance/` |
| Controllers web | `app/Http/Controllers/Finance/` |
| Controllers API | `app/Http/Controllers/Finance/Api/` |
| Rotas API (fragmentos) | `routes/api/finance.php`, `cards.php`, `projection.php`, `reports.php`, `goals.php`, `alerts.php`, `insights.php`, `credit-simulator.php`, `planning.php` |

Form requests: `app/Http/Requests/Goals/`, `app/Http/Requests/Finance/`.

### Novos endpoints (resumo)

| Prefixo `/api/...` | Função |
|--------------------|--------|
| `alerts` | GET — alertas (gasto alto, saldo negativo, fatura alta) |
| `insights` | GET — análises e frases (%, comparação mês anterior) |
| `credit-simulator` | POST `simulate` — simula parcelas |
| `planning` | CRUD — planeamento mensal (`finance_monthly_plans`) |

O mesmo exist em `/cms/finance/api/...`. Ver **`docs/finance-multi-user.md`** para isolamento por `user_id`.

A montagem das rotas HTTP está em `routes/api.php` (prefixos `finance`, `cards`, etc.) e em `routes/cms.php` (`cms/finance/api/...`).
