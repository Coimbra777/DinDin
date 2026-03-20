<?php

declare(strict_types=1);

use App\Models\Finance\Category;
use App\Models\Finance\FinanceGoal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Metas de demonstração para test@test.com.
 */
class GoalsSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->where('email', 'test@test.com')->first();
        if ($user === null) {
            $this->command?->warn('GoalsSeeder: utilizador test@test.com não encontrado.');

            return;
        }

        $incomeCategoryId = Category::query()
            ->forUser($user->id)
            ->income()
            ->inRandomOrder()
            ->value('id');

        $presets = [
            ['title' => 'Reserva de emergência', 'target' => 15000.00],
            ['title' => 'Viagem de férias', 'target' => 8000.00],
            ['title' => 'Entrada do apartamento', 'target' => 45000.00],
            ['title' => 'Curso / certificação', 'target' => 3500.00],
            ['title' => 'Troca de carro', 'target' => 25000.00],
        ];

        foreach ($presets as $row) {
            $target = (float) $row['target'];
            $current = fake()->randomFloat(2, 0, $target * 0.6);

            FinanceGoal::query()->create([
                'user_id' => $user->id,
                'title' => $row['title'],
                'description' => fake()->boolean(50) ? implode(' ', fake()->words(fake()->numberBetween(3, 8))) : null,
                'target_amount' => number_format($target, 2, '.', ''),
                'current_amount' => number_format($current, 2, '.', ''),
                'deadline' => Carbon::instance(fake()->dateTimeBetween('+1 month', '+14 months'))->format('Y-m-d'),
                'income_category_id' => $incomeCategoryId !== null && fake()->boolean(40)
                    ? $incomeCategoryId
                    : null,
            ]);
        }

        $this->command?->info('GoalsSeeder: 5 metas criadas para test@test.com.');
    }
}
