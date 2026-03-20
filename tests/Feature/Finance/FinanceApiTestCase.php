<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

abstract class FinanceApiTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    /**
     * Utilizador com módulo CMS `finance` (necessário para RestrictedController).
     */
    protected function financeUser(array $overrides = []): User
    {
        $group = Group::query()->create(['name' => 'Test Finance '.uniqid()]);
        $moduleId = DB::table('modules')->insertGetId([
            'name' => 'Finanças',
            'father_path' => null,
            'path' => 'finance',
            'order' => 1,
            'father_order' => 0,
            'icon' => 'fa',
            'has_son' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('group_module')->insert([
            'group_id' => $group->id,
            'module_id' => $moduleId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::factory()->create(array_merge([
            'group_id' => $group->id,
        ], $overrides));
    }

    protected function financeApi(string $path = ''): string
    {
        return '/cms/finance/api'.($path ? '/'.ltrim($path, '/') : '');
    }
}
