<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        $targets = [
            'actas','acta_criteria','acta_notes','acta_term_totals','acta_evaluations','nota_changes'
        ];

        if (!Schema::hasTable('information_schema')) {
            // We'll query information_schema via DB directly.
        }

        try {
            $placeholders = implode(',', array_map(function($t){ return "'".$t."'"; }, $targets));
            $sql = "SELECT TABLE_NAME,CONSTRAINT_NAME
                FROM information_schema.key_column_usage
                WHERE referenced_table_name IN ({$placeholders})
                AND constraint_schema = database()";

            $rows = DB::select($sql);
            foreach ($rows as $r) {
                $table = $r->TABLE_NAME ?? $r->table_name ?? ($r->TABLE_NAME ?? null);
                $constraint = $r->CONSTRAINT_NAME ?? $r->constraint_name ?? null;
                if ($table && $constraint) {
                    try {
                        DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint}`");
                    } catch (\Throwable $e) {
                        // ignore individual failures
                    }
                }
            }
        } catch (\Throwable $e) {
            // ignore overall failure
        }
    }

    public function down()
    {
        // intentionally empty: rebuilding original FKs automatically is risky
    }
};
