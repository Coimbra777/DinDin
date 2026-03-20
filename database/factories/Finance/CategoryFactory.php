<?php

declare(strict_types=1);

namespace Database\Factories\Finance;

use App\Models\Finance\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    /** @var list<string> */
    private static array $expenseNames = [
        'Alimentação',
        'Transporte',
        'Moradia',
        'Lazer',
        'Saúde',
        'Educação',
        'Assinaturas',
        'Pets',
    ];

    /** @var list<string> */
    private static array $incomeNames = [
        'Salário',
        'Freelance',
        'Investimentos',
        'Outras receitas',
    ];

    public function definition(): array
    {
        $type = fake()->randomElement([Category::TYPE_EXPENSE, Category::TYPE_INCOME]);

        $namePool = $type === Category::TYPE_EXPENSE ? self::$expenseNames : self::$incomeNames;

        $name = fake()->randomElement($namePool);

        return [
            'user_id' => User::factory(),
            'name' => $name.' '.fake()->unique()->numerify('##'),
            'type' => $type,
            'group' => $type === Category::TYPE_EXPENSE ? fake()->randomElement([
                Category::GROUP_FIXED,
                Category::GROUP_VARIABLE,
                Category::GROUP_FINANCIAL,
            ]) : null,
            'slug' => Str::slug($name.'-'.fake()->unique()->numerify('###')),
            'color' => fake()->randomElement(['#4caf50', '#ff0000', '#2196f3', '#ff9800', '#9c27b0']),
        ];
    }

    public function expense(): static
    {
        return $this->state(fn () => [
            'type' => Category::TYPE_EXPENSE,
            'group' => Category::GROUP_VARIABLE,
        ]);
    }

    public function income(): static
    {
        return $this->state(fn () => [
            'type' => Category::TYPE_INCOME,
            'group' => null,
        ]);
    }

    public function forUserId(int $userId): static
    {
        return $this->state(fn () => ['user_id' => $userId]);
    }
}
