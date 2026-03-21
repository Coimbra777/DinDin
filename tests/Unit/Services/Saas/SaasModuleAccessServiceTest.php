<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Saas;

use App\Contracts\ModuleAccessContract;
use App\Models\SaasModule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SaasModuleAccessServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_any_module_without_pivot(): void
    {
        $admin = User::factory()->create(['group_id' => 0, 'is_admin' => true]);
        $access = app(ModuleAccessContract::class);

        $this->assertTrue($access->can($admin, 'finance'));
        $this->assertTrue($access->can($admin, 'reports'));
    }

    public function test_non_admin_only_with_matching_slug(): void
    {
        $user = User::factory()->create(['group_id' => 0, 'is_admin' => false]);
        $finance = SaasModule::query()->where('slug', 'finance')->firstOrFail();
        $user->saasModules()->attach($finance->id);

        $access = app(ModuleAccessContract::class);
        $this->assertTrue($access->can($user, 'finance'));
        $this->assertFalse($access->can($user, 'reports'));
    }

    public function test_non_admin_without_pivot_has_base_finance_only(): void
    {
        $user = User::factory()->create(['group_id' => 0, 'is_admin' => false]);
        $access = app(ModuleAccessContract::class);

        $this->assertTrue($access->can($user, 'finance'));
        $this->assertFalse($access->can($user, 'reports'));
    }

    public function test_non_admin_with_unrelated_slug_still_has_finance_not_other_modules(): void
    {
        $user = User::factory()->create(['group_id' => 0, 'is_admin' => false]);
        $otherId = DB::table('saas_modules')->insertGetId([
            'name' => 'Outro',
            'slug' => 'other',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user->saasModules()->attach($otherId);

        $access = app(ModuleAccessContract::class);
        $this->assertTrue($access->can($user, 'finance'));
        $this->assertFalse($access->can($user, 'reports'));
    }
}
