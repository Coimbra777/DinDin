# Módulo Finance (CMS)

Controlo de gastos — UI dedicada (Vue 2 + Vuetify 2), sem menu AdminLTE nas rotas de finanças.

## Configuração (`config/finance.php`)

| Chave `.env` | Efeito |
|--------------|--------|
| `FINANCE_STANDALONE_UI=true` | Layout só Vuetify + categorias na SPA (padrão `true` no config) |
| `FINANCE_REDIRECT_DASHBOARD=true` | `/cms/dashboard` e login pós-auth vão para o dashboard financeiro |

## Estrutura

Ver **`app/Modules/README.md`** para a visão global (Finance, CreditCard, Projection, Reports).

```
app/Modules/Finance/
├── Http/Controllers/
│   ├── Api/                            # JSON modular (dashboard, summary, transactions, categories)
│   ├── FinanceDashboardController.php
│   ├── TransactionController.php       # Web Blade
│   └── CategoryController.php
├── Models/
│   ├── Transaction.php
│   └── Category.php
├── Services/                           # Lógica de negócio / serialização
├── Routes/api.php                      # Montado em /api/finance/*
app/Modules/CreditCard/                 # Cartões + fatura
app/Modules/Projection/                 # Projeção
app/Modules/Reports/                    # Relatórios agregados
resources/views/cms/
├── layouts/finance-shell.blade.php
└── finance/spa.blade.php
resources/assets/js/finance/
├── currency.js
├── finance-app.js
├── pages/finance/FinanceApp.vue
├── pages/cards/CreditCardsPage.vue
├── pages/reports/ReportsPage.vue
└── components/
database/migrations/
├── *_create_finance_categories_table.php
├── *_add_type_and_group_to_finance_categories_table.php  # type income|expense, group opcional
├── *_create_finance_transactions_table.php
├── *_add_finance_modules_to_menu.php
└── *_add_finance_dashboard_module.php
database/seeds/
└── FinanceCategorySeeder.php   # categorias padrão por utilizador (firstOrCreate)
```

### Categorias padrão (seed)

Após `php artisan migrate`, execute `php artisan db:seed --class=FinanceCategorySeeder` (ou o `DatabaseSeeder` completo, depois do `UsersSeeder`).  
Cada utilizador recebe receitas (Salário, 13º, Férias, Outras rendas) e despesas agrupadas em `group`: **fixa**, **variavel**, **financeira**.  
`type` na categoria: `income` ou `expense` (transações continuam com o próprio `type`; a categoria serve para UI/agrupamento).
```

## Rotas (prefixo `cms/finance/`)

| Nome | Descrição |
|------|-----------|
| `finance_dashboard.index` | Resumo: saldo geral, gasto/receitas no mês, **ainda posso gastar** |
| `finance_transactions.*` | CRUD transações |
| `finance_categories.*` | CRUD categorias |

## Regra de negócio principal

- **Saldo real (caixa no mês):** receitas do mês − despesas **à vista** (transações que **não** são `is_credit_card`).
- **Saldo com cartão:** receitas − todas as despesas (inclui gastos no cartão no mês civil).
- Despesas no cartão entram na **fatura** / visão consolidada; o dashboard separa os dois saldos.

- Não há orçamento por categoria nesta versão (expansão futura).
- Se o utilizador não registar todas as receitas, o número reflete apenas o que está no sistema.

## Filtros (transações)

- **Mês** (`YYYY-MM`)
- **Categoria** (opcional)

## Interface Vue + Vuetify (SPA no CMS)

O projeto usa **Vue 2.7 + Vuetify 2** (já no `package.json`). **Vue 3 + Vuetify 3** exigiria plugin Vite e upgrade globais.

- **Entrada Vite:** `resources/assets/js/finance/finance-app.js`
- **View única:** `resources/views/cms/finance/spa.blade.php` (Resumo e Transações abrem a mesma SPA)
- **API JSON (sessão + CSRF):** prefixo `cms/finance/api/` — mesmos recursos; também `reports/categories` e `reports/trend`.
- **API modular (REST):** ` /api/finance/*`, `/api/cards/*`, `/api/projection`, `/api/reports/*` (ver `routes/api.php`).
- **Dashboard JSON:** `GET /cms/finance/api/dashboard` (recomendado na SPA — mesma sessão e CSRF que `categories`). Opcionalmente `GET /api/dashboard` com middleware `web`+`auth`. Uma query agrega o mês (`aggregateMonthStats`). Resposta (`?month=YYYY-MM`): `saldo_real` e `saldo_atual` (iguais: caixa no mês), `saldo_com_cartao` (consolidado incluindo cartão), `receitas_mes`, `despesas_mes` / `despesas_caixa_mes`, `despesas_cartao_mes`, `total_transacoes`, `ultimas_transacoes` (do mês), `month`.
- **Projeção (12 meses):** `GET /cms/finance/api/projection` ou `GET /api/projection`. Resposta: `meses[]` (`mes`, `label`, `receitas_previstas`, `despesas_previstas`, `saldo_projetado`) e `meta` (médias base excluindo parcelas, `meses_referencia`, `parcelas_detectadas`). Transações podem ter `installment_number` / `installment_of`; também se infere padrão `3/12` ou “parcela 3 de 12” no título/descrição.
- **Componentes:** `resources/assets/js/finance/components/` — `BalanceSummary`, `FinanceCard`, `TransactionList`, `TransactionForm` (modal)

Moeda de exibição na UI: **R$** (pt-BR). Valores no backend continuam numéricos.

## UX sugerida (evolução)

1. Manter o **Resumo** como entrada principal do menu Finanças.
2. **Uma ação principal** no resumo: «+ Despesa rápida» / «+ Receita» (atalhos).
3. Mostrar sempre um texto curto sob o valor principal (evita interpretações erradas).
4. Futuro: meta de gasto mensal → “ainda posso gastar” = meta − despesas (sem depender de receitas).
