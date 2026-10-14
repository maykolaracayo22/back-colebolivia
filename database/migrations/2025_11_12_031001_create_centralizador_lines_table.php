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
        if (!Schema::hasTable('centralizador_lines')) {
            Schema::create('centralizador_lines', function (Blueprint $table) {
                $table->id();
                $table->integer('year');
                $table->string('nivel');
                $table->string('grado');
                $table->string('seccion');
                $table->foreignId('materia_id')->constrained('subjects');
                $table->foreignId('student_id')->constrained('students');
                $table->integer('t1')->nullable()->comment('nota trimestre 1');
                $table->integer('t2')->nullable()->comment('nota trimestre 2');
                $table->integer('t3')->nullable()->comment('nota trimestre 3');
                $table->integer('pa')->nullable()->comment('promedio anual (t1+t2+t3)/3');
                $table->enum('status', ['DRAFT', 'PUBLISHED'])->default('DRAFT');
                $table->dateTime('published_at')->nullable();
                $table->foreignId('published_by')->nullable()->constrained('users');
                $table->timestamps();

                $table->unique(['year', 'materia_id', 'student_id'], 'centralizador_unique');
                $table->index(['year', 'materia_id', 'grado', 'seccion']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('centralizador_lines');
    }
};
