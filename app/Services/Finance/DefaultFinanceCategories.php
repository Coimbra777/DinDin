<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Finance\Category;
use Illuminate\Support\Str;

/**
 * Catálogo padrão de categorias por utilizador (receitas / despesas).
 * Usado no registo e no {@see \FinanceCategorySeeder}.
 */
final class DefaultFinanceCategories
{
    /**
     * @return list<array{name: string, type: string, group: string|null, color: string}>
     */
    public static function definitions(): array
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
            ['name' => 'Outros', 'type' => Category::TYPE_EXPENSE, 'group' => Category::GROUP_VARIABLE, 'color' => '#64748b'],
        ];
    }

    public static function ensureForUserId(int $userId): void
    {
        foreach (self::definitions() as $row) {
            Category::firstOrCreate(
                [
                    'user_id' => $userId,
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
    }
}
