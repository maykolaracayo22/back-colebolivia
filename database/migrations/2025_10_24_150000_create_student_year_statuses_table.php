<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('student_year_statuses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $t->integer('year');
            $t->enum('nivel', ['inicial','primaria','secundaria']);
            $t->unsignedTinyInteger('grado'); // 0: PK, 1..6
            $t->string('seccion', 10)->nullable();

            $t->enum('estado', ['EN_CURSO','APLAZO','PROMOVIDO','PROMOVIDO_RECUPERACION','REPROBADO','EGRESADO'])
              ->default('EN_CURSO');

            $t->decimal('promedio_final', 5, 2)->nullable();
            $t->unsignedSmallInteger('reprobadas')->default(0);
            $t->decimal('promedio_extra', 5, 2)->nullable();

            $t->timestamp('decided_at')->nullable();
            $t->json('meta')->nullable(); // logs breves: materias reprobadas, etc.

            $t->timestamps();

            $t->unique(['student_id','year']);
            $t->index(['year','nivel','grado','estado']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('student_year_statuses');
    }
};
