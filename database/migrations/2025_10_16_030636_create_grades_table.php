<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('grades', function (Blueprint $t) {
            $t->id();
            $t->string('year',4);
            $t->foreignId('student_id')->constrained()->cascadeOnDelete();
            $t->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $t->unsignedTinyInteger('peso')->default(100); // si usas ítems, luego separas
            $t->enum('period_code',['T1','T2','T3']);
            $t->decimal('puntaje',5,2)->default(0);
            $t->boolean('publicado')->default(false);
            $t->timestamps();

            $t->unique(['year','student_id','subject_id','period_code']);
        });
    }
    public function down(): void { Schema::dropIfExists('grades'); }
};
