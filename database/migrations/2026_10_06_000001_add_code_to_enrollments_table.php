<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('enrollments', 'code')) {
            Schema::table('enrollments', function (Blueprint $table) {
                // Nullable during insert because the model assigns the final code after the ID exists.
                $table->string('code')->nullable()->unique()->after('fecha');
            });
        }

        DB::table('enrollments')
            ->whereNull('code')
            ->orderBy('id')
            ->chunkById(500, function ($enrollments) {
                foreach ($enrollments as $enrollment) {
                    DB::table('enrollments')
                        ->where('id', $enrollment->id)
                        ->update([
                            'code' => 'MTR-' . $enrollment->year . '-' . str_pad((string) $enrollment->id, 4, '0', STR_PAD_LEFT),
                        ]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('enrollments', 'code')) {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->dropUnique(['code']);
                $table->dropColumn('code');
            });
        }
    }
};
