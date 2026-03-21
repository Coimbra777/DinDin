<?php

declare(strict_types=1);

namespace Tests\Feature\Saas;

use App\Models\SaasModule;
use App\Models\User;
use Tests\Feature\Finance\FinanceApiTestCase;

class SaasFinanceEntitlementTest extends FinanceApiTestCase
{
    public function test_user_with_finance_slug_in_pivot_can_access_finance_dashboard_api(): void
    {
        $user = User::factory()->create(['group_id' => 0, 'is_admin' => false]);
        $finance = SaasModule::query()->where('slug', 'finance')->firstOrFail();
        $user->saasModules()->attach($finance->id);

        $this->actingAs($user)
            ->getJson($this->financeApi('dashboard'))
            ->assertOk();
    }

    public function test_user_without_saas_pivot_can_access_finance_api_base_entitlement(): void
    {
        $user = User::factory()->create(['group_id' => 0, 'is_admin' => false]);

        $this->actingAs($user)
            ->getJson($this->financeApi('dashboard'))
            ->assertOk();
    }
}
