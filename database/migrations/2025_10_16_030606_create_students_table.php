<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('students', function (Blueprint $t) {
            $t->id();
            $t->string('rude')->nullable()->unique();
            $t->string('ci')->nullable();
            $t->string('nombres');
            $t->string('apellidos');
            $t->enum('nivel', ['inicial','primaria','secundaria']);
            $t->string('curso');
            $t->string('paralelo');
            $t->boolean('activo')->default(true);

            $t->string('email')->nullable();
            $t->string('telefono')->nullable();
            $t->string('codigo')->unique(); // EST-YYYY-###

            // Credenciales
            $t->string('usuario')->unique();
            $t->string('password_temp');
            $t->string('anio_ingreso');
            $t->string('mes_ingreso');

            $t->date('fecha_nac')->nullable();
            $t->enum('sexo',['M','F'])->nullable();
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('students'); }
};
