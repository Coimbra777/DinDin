<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $now = now();

        DB::table('users')->insert([
            [
                'name' => env('SEED_ADMIN_NAME', 'Administrador'),
                'email' => env('SEED_ADMIN_EMAIL', 'admin@example.com'),
                'password' => bcrypt('123456'),
                'remember_token' => Str::random(60),
                'created_at' => $now,
                'updated_at' => $now,
                'username' => 'admin',
                'group_id' => 1,
                'deleted_at' => null,
            ],
            [
                'name' => 'Utilizador teste finanças',
                'email' => 'test@test.com',
                'password' => bcrypt('123456'),
                'remember_token' => Str::random(60),
                'created_at' => $now,
                'updated_at' => $now,
                'username' => 'test',
                'group_id' => 1,
                'deleted_at' => null,
            ],
        ]);
    }
}
