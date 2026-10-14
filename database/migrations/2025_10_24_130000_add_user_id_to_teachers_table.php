<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            // Columna + FK (nullable) hacia users, con nullOnDelete por seguridad
            $table->foreignId('user_id')
                  ->nullable()
                  ->after('id')
                  ->constrained('users')
                  ->nullOnDelete();

            // Índice UNIQUE sobre user_id (MySQL permite múltiples NULL en UNIQUE)
            $table->unique(['user_id']);
        });
    }

    public function down(): void
    {
        // Solo si existe la columna user_id intentamos limpiar índices/FK/columna
        if (Schema::hasColumn('teachers', 'user_id')) {

            // 1) Intentar soltar UNIQUE de varias formas (defensivo)
            try {
                Schema::table('teachers', function (Blueprint $table) {
                    // nombre típico que genera Laravel
                    $table->dropUnique('teachers_user_id_unique');
                });
            } catch (\Throwable $e) {
                // ignoramos si no existe
            }

            try {
                Schema::table('teachers', function (Blueprint $table) {
                    // por columnas (Laravel generará el nombre)
                    $table->dropUnique(['user_id']);
                });
            } catch (\Throwable $e) {
                // ignoramos si no existe
            }

            // 2) Soltar FK + columna en un paso (no depende del nombre de la FK)
            Schema::table('teachers', function (Blueprint $table) {
                $table->dropConstrainedForeignId('user_id');
            });
        }
    }
};
