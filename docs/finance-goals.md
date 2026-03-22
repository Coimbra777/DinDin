# Metas financeiras (Goals)

- Model: `App\Models\Finance\FinanceGoal`
- Service / resource: `App\Services\Finance\FinanceGoalService`, `FinanceGoalResource`
- API (canónica): `GET/POST /cms/finance/api/goals`, `GET/PUT/DELETE /cms/finance/api/goals/{id}`, `POST /cms/finance/api/goals/{id}/sync`

Form requests: `App\Http\Requests\Goals\StoreFinanceGoalRequest`, `UpdateFinanceGoalRequest`.
