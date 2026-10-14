<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('students', function (Blueprint $t) {
            $t->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete()->unique();
        });
    }
    public function down(): void
    {
        if (!Schema::hasTable('students') || !Schema::hasColumn('students', 'user_id')) {
            return;
        }
        
        try {
            Schema::table('students', function (Blueprint $t) {
                $t->dropUnique(['user_id']);
            });
        } catch (\Exception $e) {
            // Índice no existe, continuar
        }
        
        try {
            Schema::table('students', function (Blueprint $t) {
                $t->dropConstrainedForeignId('user_id');
            });
        } catch (\Exception $e) {
            // Foreign key no existe, continuar
        }
    }
};
