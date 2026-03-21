<?php

declare(strict_types=1);

namespace Tests\Feature\Saas;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaasAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    public function test_non_admin_cannot_access_admin_users_list(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get('/cms/admin/users')
            ->assertForbidden();
    }

    public function test_admin_can_access_admin_users_list(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get('/cms/admin/users')
            ->assertOk();
    }

    public function test_user_can_access_finance_api_with_base_entitlement_without_pivot(): void
    {
        $user = User::factory()->create(['group_id' => 0, 'is_admin' => false]);

        $this->actingAs($user)
            ->getJson('/api/finance/dashboard')
            ->assertOk();
    }

    public function test_admin_accesses_finance_api_without_group_or_pivot(): void
    {
        $admin = User::factory()->create(['group_id' => 0, 'is_admin' => true]);

        $this->actingAs($admin)
            ->getJson('/api/finance/dashboard')
            ->assertOk();
    }
}
