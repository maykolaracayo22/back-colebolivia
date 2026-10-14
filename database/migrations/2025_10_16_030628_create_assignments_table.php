<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('assignments', function (Blueprint $t) {
            $t->id();
            $t->string('year',4);
            $t->foreignId('section_id')->constrained()->cascadeOnDelete();
            $t->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $t->foreignId('teacher_id')->constrained()->restrictOnDelete();
            $t->enum('dia',['Lun','Mar','Mié','Jue','Vie']);
            $t->time('inicio');
            $t->time('fin');
            $t->string('aula')->nullable();
            $t->boolean('titular')->default(true);
            $t->timestamps();

            $t->index(['year','teacher_id','dia','inicio','fin']);
            $t->index(['year','section_id','dia','inicio','fin']);
            $t->index(['year','aula','dia','inicio','fin']);
        });
    }
    public function down(): void { Schema::dropIfExists('assignments'); }
};
