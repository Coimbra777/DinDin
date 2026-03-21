<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $financeId = DB::table('saas_modules')->where('slug', 'finance')->value('id');
        if ($financeId === null) {
            return;
        }
        $financeId = (int) $financeId;
        $now = now();

        User::query()->orderBy('id')->chunkById(500, function ($users) use ($financeId, $now): void {
            foreach ($users as $user) {
                $exists = DB::table('saas_module_user')
                    ->where('user_id', $user->id)
                    ->where('saas_module_id', $financeId)
                    ->exists();
                if ($exists) {
                    continue;
                }
                DB::table('saas_module_user')->insert([
                    'user_id' => $user->id,
                    'saas_module_id' => $financeId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });
    }

    public function down(): void
    {
        // Sem reversão: não remover linhas que possam ter sido criadas manualmente depois.
    }
};
