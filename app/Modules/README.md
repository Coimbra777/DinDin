# Módulos Laravel (domínio)

Estrutura modular por pasta em `app/Modules/{Nome}/`, cada um com **Controllers**, **Models** (quando aplicável), **Services** e **`Routes/api.php`** (fragmento incluído a partir de `routes/api.php`).

## Árvore

```
app/Modules/
├── Finance/                    # Transações, categorias, dashboard
│   ├── Http/Controllers/
│   │   ├── Api/                # DashboardApiController, SummaryApiController, …
│   │   ├── TransactionController.php   # Web (Blade legado)
│   │   ├── CategoryController.php
│   │   └── FinanceDashboardController.php
│   ├── Models/
│   │   ├── Transaction.php
│   │   └── Category.php
│   ├── Services/
│   │   ├── DashboardService.php
│   │   ├── SummaryService.php
│   │   ├── TransactionApiService.php
│   │   ├── CategoryApiService.php
│   │   └── TransactionResource.php
│   └── Routes/api.php
│
├── CreditCard/                 # Cartões, faturas, limite
│   ├── Http/Controllers/Api/CreditCardApiController.php
│   ├── Models/CreditCard.php
│   ├── Services/
│   │   ├── CreditCardService.php
│   │   └── CreditCardBillingService.php
│   └── Routes/api.php
│
├── Projection/                 # Previsão de saldo
│   ├── Http/Controllers/Api/ProjectionApiController.php
│   ├── Services/FinanceProjectionService.php
│   └── Routes/api.php
│
└── Reports/                    # Gráficos / análises agregadas
    ├── Http/Controllers/Api/ReportApiController.php
    ├── Services/ReportService.php
    └── Routes/api.php
```

## Rotas HTTP (`routes/api.php`)

Prefixo global `api/` + middleware `web` + `auth` (sessão CMS):

| Prefixo | Módulo |
|---------|--------|
| `/api/finance/*` | Finance |
| `/api/cards/*` | CreditCard |
| `/api/projection` | Projection |
| `/api/reports/*` | Reports |

Aliases legados na raiz de `/api`: `dashboard`, `projection`, `credit-cards/*`.

**SPA (recomendado):** continua em `GET /cms/finance/api/...` (mesmos controladores modulares + relatórios em `.../reports/*`).

## Frontend (`resources/assets/js/finance/`)

- `pages/finance/FinanceApp.vue` — shell principal
- `pages/cards/CreditCardsPage.vue`
- `pages/reports/ReportsPage.vue`
- `components/*` — UI partilhada

## Exemplo de módulo completo — **CreditCard**

1. **`Models/CreditCard.php`** — Eloquent, tabela `finance_credit_cards`.
2. **`Services/CreditCardService.php`** — CRUD e serialização (`toArray`).
3. **`Services/CreditCardBillingService.php`** — regra de fatura / período de fechamento.
4. **`Http/Controllers/Api/CreditCardApiController.php`** — só valida `Request` e delega ao service.
5. **`Routes/api.php`** — `GET /`, `POST /`, `PUT /{finance_credit_card}`, `DELETE /...`, `GET /.../bill` (prefixo pai: `cards`).

Binding de rota: `finance_credit_card` em `RouteServiceProvider` resolve por `user_id`.
