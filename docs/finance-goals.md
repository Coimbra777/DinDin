# Metas financeiras (Goals)

- Model: `App\Models\Finance\FinanceGoal`
- Service / resource: `App\Services\Finance\FinanceGoalService`, `FinanceGoalResource`
- API: `GET/POST /api/goals`, `GET/PUT/DELETE /api/goals/{id}`, `POST /api/goals/{id}/sync`
- SPA (mesma sessão CMS): prefixo ` /cms/finance/api/goals`

Form requests: `App\Http\Requests\Goals\StoreFinanceGoalRequest`, `UpdateFinanceGoalRequest`.
