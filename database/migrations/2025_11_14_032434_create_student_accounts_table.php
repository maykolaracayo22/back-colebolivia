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
        Schema::create('student_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->year('year');
            $table->decimal('total_debt', 10, 2)->default(0);
            $table->decimal('total_paid', 10, 2)->default(0);
            $table->enum('status', ['AL_DIA', 'CON_DEUDA', 'EGRESADO', 'RETIRADO'])->default('AL_DIA');
            $table->timestamps();

            // Índices
            $table->unique(['student_id', 'year']);
            $table->index('status');
            $table->index('year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_accounts');
    }
};
