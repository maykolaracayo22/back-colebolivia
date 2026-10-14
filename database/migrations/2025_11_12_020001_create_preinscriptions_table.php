<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('preinscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_preinscripcion')->unique();
            $table->enum('nivel',['inicial','primaria','secundaria']);
            $table->tinyInteger('grado');
            $table->string('nombres');
            $table->string('apellidos');
            $table->enum('sexo',['M','F']);
            $table->date('fecha_nac');
            $table->string('ci')->nullable();
            $table->string('nacionalidad')->nullable();
            $table->string('dep_nac')->nullable();
            $table->string('muni_nac')->nullable();
            $table->text('direccion')->nullable();
            $table->string('telefono')->nullable();
            $table->string('email')->nullable();
            $table->string('pueblo_indigena')->nullable();
            $table->boolean('discapacidad')->default(false);
            $table->json('idiomas')->nullable();
            $table->boolean('vulnerabilidad')->default(false);
            $table->string('tutor_nombre');
            $table->string('tutor_ci')->nullable();
            $table->string('tutor_parentesco');
            $table->string('tutor_telefono')->nullable();
            $table->string('comprobante_path')->nullable(); // Path al archivo subido
            $table->enum('estado',['pendiente','aprobado','rechazado'])->default('pendiente');
            $table->text('observaciones')->nullable();
            $table->foreignId('aprobado_por')->nullable()->constrained('users');
            $table->timestamp('aprobado_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('preinscriptions');
    }
};
