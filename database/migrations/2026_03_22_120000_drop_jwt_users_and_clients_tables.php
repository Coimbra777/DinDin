<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove o legado JWT: jwt_users (credenciais token) e clients (perfil espelhado por email).
     * A identidade única passa a ser apenas `users`.
     */
    public function up(): void
    {
        Schema::dropIfExists('jwt_users');
        Schema::dropIfExists('clients');
    }

    public function down(): void
    {
        // Irreversível sem recriar esquema legado completo; restaurar via backup se necessário.
    }
};
