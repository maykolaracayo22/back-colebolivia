<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Agrega campos adicionales a la tabla parents
     */
    public function up(): void
    {
        Schema::table('parents', function (Blueprint $table) {
            if (!Schema::hasColumn('parents', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            }
            if (!Schema::hasColumn('parents', 'ci')) {
                $table->string('ci', 20)->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('parents', 'phone')) {
                $table->string('phone', 20)->nullable()->after('ci');
            }
            if (!Schema::hasColumn('parents', 'address')) {
                $table->string('address', 255)->nullable()->after('phone');
            }
            if (!Schema::hasColumn('parents', 'ocupacion')) {
                $table->string('ocupacion', 100)->nullable()->after('address');
            }
            if (!Schema::hasColumn('parents', 'lugar_trabajo')) {
                $table->string('lugar_trabajo', 255)->nullable()->after('ocupacion');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parents', function (Blueprint $table) {
            $table->dropColumn(['user_id', 'ci', 'phone', 'address', 'ocupacion', 'lugar_trabajo']);
        });
    }
};
