<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('enrollments', function (Blueprint $t) {
            $t->id();
            $t->string('year',4);
            $t->foreignId('student_id')->constrained()->cascadeOnDelete();
            $t->foreignId('section_id')->constrained()->restrictOnDelete();
            $t->enum('estado',['PREINSCRITO','INSCRITO','BAJA_TEMP','BAJA_DEF'])->default('INSCRITO');
            $t->date('fecha')->nullable();
            $t->timestamps();

            $t->unique(['year','student_id']); // un estudiante por año
        });
    }
    public function down(): void { Schema::dropIfExists('enrollments'); }
};
