<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Models\SaasModule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
     * Utilizador com todos os módulos SaaS usados pela SPA/API de testes (pivot explícito).
     */
    protected function financeUser(array $overrides = []): User
    {
        $user = User::factory()->create(array_merge(['group_id' => 0], $overrides));

        $ids = SaasModule::query()
            ->whereIn('slug', ['finance', 'cards', 'reports', 'projections', 'planning'])
            ->pluck('id')
            ->all();

        if ($ids !== []) {
            $user->saasModules()->sync($ids);
        }

        return $user;
    }

    protected function financeApi(string $path = ''): string
    {
        return '/cms/finance/api'.($path ? '/'.ltrim($path, '/') : '');
    }
}
