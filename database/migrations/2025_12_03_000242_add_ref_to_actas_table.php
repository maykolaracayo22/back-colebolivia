<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actas', function (Blueprint $table) {
            // ref tipo string, único, después de docente_nombre
            if (!Schema::hasColumn('actas', 'ref')) {
                $table->string('ref')->unique()->after('docente_nombre');
            }
        });
    }

    public function down(): void
    {
        Schema::table('actas', function (Blueprint $table) {
            if (Schema::hasColumn('actas', 'ref')) {
                $table->dropColumn('ref');
            }
        });
    }
};
