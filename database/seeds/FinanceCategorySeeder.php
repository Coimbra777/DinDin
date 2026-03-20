<?php

declare(strict_types=1);

use App\Models\Finance\Category;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

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

        $definitions = $this->definitions();

        User::query()->orderBy('id')->each(function (User $user) use ($definitions): void {
            foreach ($definitions as $row) {
                Category::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'name' => $row['name'],
                    ],
                    [
                        'type' => $row['type'],
                        'group' => $row['group'],
                        'color' => $row['color'],
                        'slug' => Str::slug($row['name']),
                    ]
                );
            }
        });
    }

    /**
     * @return list<array{name: string, type: string, group: string|null, color: string}>
     */
    private function definitions(): array
    {
        return [
            ['name' => 'Salário', 'type' => Category::TYPE_INCOME, 'group' => null, 'color' => '#16a34a'],
            ['name' => 'Freelance', 'type' => Category::TYPE_INCOME, 'group' => null, 'color' => '#15803d'],
            ['name' => 'Vendas', 'type' => Category::TYPE_INCOME, 'group' => null, 'color' => '#22c55e'],
            ['name' => 'Investimentos', 'type' => Category::TYPE_INCOME, 'group' => null, 'color' => '#4ade80'],

            ['name' => 'Alimentação', 'type' => Category::TYPE_EXPENSE, 'group' => Category::GROUP_VARIABLE, 'color' => '#ea580c'],
            ['name' => 'Transporte', 'type' => Category::TYPE_EXPENSE, 'group' => Category::GROUP_VARIABLE, 'color' => '#c2410c'],
            ['name' => 'Moradia', 'type' => Category::TYPE_EXPENSE, 'group' => Category::GROUP_FIXED, 'color' => '#dc2626'],
            ['name' => 'Lazer', 'type' => Category::TYPE_EXPENSE, 'group' => Category::GROUP_VARIABLE, 'color' => '#9a3412'],
            ['name' => 'Assinaturas', 'type' => Category::TYPE_EXPENSE, 'group' => Category::GROUP_FINANCIAL, 'color' => '#7c3aed'],
        ];
    }
}
