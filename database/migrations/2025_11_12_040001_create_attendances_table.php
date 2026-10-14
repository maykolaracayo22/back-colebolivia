<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('section_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->date('fecha');
            $table->enum('estado',['presente','ausente','tardanza','justificado'])->default('presente');
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->unique(['student_id','fecha','subject_id'],'attendance_unique');
        });
    }

    public function down(): void {
        Schema::dropIfExists('attendances');
    }
};
