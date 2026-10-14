<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('acta_notes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('acta_id')->constrained('actas')->cascadeOnDelete();
            $t->foreignId('evaluation_id')->nullable()->constrained('acta_evaluations')->nullOnDelete();
            $t->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $t->enum('tipo',['NUM','QUAL'])->default('NUM');
            $t->decimal('valor',6,2)->nullable();
            $t->string('valor_qual',50)->nullable();
            $t->timestamps();

            $t->unique(['acta_id','evaluation_id','student_id']);
            $t->index(['acta_id','student_id']);
        });
    }

    public function down(): void { Schema::dropIfExists('acta_notes'); }
};
