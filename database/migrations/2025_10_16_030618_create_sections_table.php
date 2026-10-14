<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
Schema::create('sections', function (Blueprint $table) {
    $table->id();
    $table->string('year', 4);
    $table->enum('nivel', ['inicial','primaria','secundaria']);
    $table->string('curso');
    $table->string('paralelo', 3);
    $table->enum('turno', ['Mañana','Tarde','Noche']);
    $table->string('aula')->nullable();
    $table->unsignedInteger('capacidad')->nullable();
    $table->foreignId('tutor_id')->nullable()->constrained('teachers')->nullOnDelete();
    $table->string('tutor_nombre')->nullable();
    $table->enum('estado', ['ACTIVA','CERRADA'])->default('ACTIVA');
    $table->timestamps();

    $table->unique(['year','nivel','curso','paralelo']); // clave lógica
});

    }
    public function down(): void { Schema::dropIfExists('sections'); }
};
