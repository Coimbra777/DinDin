<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\Finance\DefaultFinanceCategories;
use Illuminate\Database\Seeder;

/**
 * Categorias padrão de receitas e despesas (estilo planilha de controle financeiro).
 * Não duplica: usa firstOrCreate com o unique (user_id, name) da tabela.
 */
class FinanceCategorySeeder extends Seeder
{
    public function run(): void
    {
        if (User::query()->doesntExist()) {
            $this->command?->warn('FinanceCategorySeeder: sem utilizadores na tabela users; nada a criar.');

            return;
        }

        User::query()->orderBy('id')->each(function (User $user): void {
            DefaultFinanceCategories::ensureForUserId((int) $user->id);
        });
    }
}
