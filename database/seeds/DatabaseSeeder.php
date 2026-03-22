<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(ModuleSeeder::class);
        $this->call(UsersSeeder::class);
        $this->call(FinanceCategorySeeder::class);
        $this->call(ConfigSeeder::class);

        $this->call([
            FinanceSeeder::class,
            GoalsSeeder::class,
            PlanningSeeder::class,
        ]);

        if (filter_var(env('SEED_FINANCIAL_TEST_DATA', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->call(FinancialTestDataSeeder::class);
        }
    }
}
