<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('conduct_incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('reported_by')->constrained('users')->onDelete('cascade');
            $table->enum('tipo',['positiva','negativa'])->default('negativa');
            $table->string('descripcion');
            $table->date('fecha');
            $table->enum('severidad',['leve','moderada','grave'])->default('leve');
            $table->text('acciones_tomadas')->nullable();
            $table->foreignId('aprobado_por')->nullable()->constrained('users');
            $table->timestamp('aprobado_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('conduct_incidents');
    }
};
