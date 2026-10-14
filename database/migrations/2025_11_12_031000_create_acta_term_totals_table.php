<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('acta_term_totals')) {
            Schema::create('acta_term_totals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('acta_id')->constrained('actas');
                $table->foreignId('student_id')->constrained('students');
                $table->integer('autoevaluacion')->nullable()->comment('0..5');
                $table->integer('pe')->nullable()->comment('Prueba Escrita');
                $table->integer('tspe')->default(0)->comment('SER+SABER+HACER+DECIDIR+auto');
                $table->integer('tn')->default(0)->comment('nota trimestre = tspe + pe');
                $table->timestamps();

                $table->unique(['acta_id', 'student_id']);
                $table->index(['acta_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acta_term_totals');
    }
};
