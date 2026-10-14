<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // If the table or the FK does not exist, this migration will not fail
        if (!Schema::hasTable('nota_changes')) {
            return;
        }

        try {
            // Attempt to drop the foreign key by name if present
            DB::statement('ALTER TABLE `nota_changes` DROP FOREIGN KEY `nota_changes_nota_id_foreign`');
        } catch (\Throwable $e) {
            // ignore: FK may not exist or DB engine may differ
        }

        try {
            // Also try a generic approach: drop foreign keys that reference acta_notes
            $rows = DB::select("SELECT constraint_name FROM information_schema.key_column_usage WHERE referenced_table_name = 'acta_notes' AND table_name = 'nota_changes' AND constraint_schema = database()");
            foreach ($rows as $r) {
                $name = $r->constraint_name ?? $r->CONSTRAINT_NAME ?? null;
                if ($name) {
                    DB::statement("ALTER TABLE `nota_changes` DROP FOREIGN KEY `{$name}`");
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public function down()
    {
        // Intentionally empty: restoring FK automatically is risky without knowing original schema
    }
};
