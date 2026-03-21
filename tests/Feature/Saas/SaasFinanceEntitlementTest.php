<?php

declare(strict_types=1);

namespace Tests\Feature\Saas;

use App\Models\SaasModule;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Finance\FinanceApiTestCase;

class SaasFinanceEntitlementTest extends FinanceApiTestCase
{
    public function test_legacy_group_finance_still_allows_cms_finance_api_when_no_saas_rows(): void
    {
        $user = $this->financeUser();

        $this->actingAs($user)
            ->getJson($this->financeApi('dashboard'))
            ->assertOk();
    }

    public function test_explicit_saas_rows_without_finance_denies_even_if_group_has_finance(): void
    {
        $user = $this->financeUser();

        $otherId = DB::table('saas_modules')->insertGetId([
            'name' => 'Outro',
            'slug' => 'other',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user->saasModules()->attach($otherId);

        $this->actingAs($user)
            ->getJson($this->financeApi('dashboard'))
            ->assertForbidden();
    }

    public function test_saas_finance_pivot_allows_without_cms_group_module(): void
    {
        $user = User::factory()->create(['group_id' => 0, 'is_admin' => false]);
        $finance = SaasModule::query()->where('slug', 'finance')->firstOrFail();
        $user->saasModules()->attach($finance->id);

        $this->actingAs($user)
            ->getJson($this->financeApi('dashboard'))
            ->assertOk();
    }
}
