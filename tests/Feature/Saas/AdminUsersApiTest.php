<?php

declare(strict_types=1);

namespace Tests\Feature\Saas;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUsersApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    public function test_admin_can_list_users_json(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->count(2)->create(['is_admin' => false]);

        $this->actingAs($admin)
            ->getJson('/cms/admin/api/users')
            ->assertOk()
            ->assertJsonStructure(['data', 'total', 'per_page']);
    }

    public function test_non_admin_cannot_list_users_api(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->getJson('/cms/admin/api/users')
            ->assertForbidden();
    }
}
