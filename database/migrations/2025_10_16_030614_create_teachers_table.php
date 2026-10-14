<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('teachers', function (Blueprint $t) {
            $t->id();
            $t->string('ci')->unique();
            $t->string('nombres');
            $t->string('apellidos');
            $t->string('email')->unique();
            $t->string('telefono')->nullable();
            $t->string('rda_numero')->unique();
            $t->enum('rda_estado',['VIGENTE','OBSERVADO','VENCIDO'])->default('VIGENTE');
            $t->date('rda_vencimiento')->nullable();
            $t->enum('estado', ['activo','licencia','inactivo'])->default('activo');

            // Credenciales
            $t->string('usuario')->unique();
            $t->string('anio_ingreso');
            $t->string('mes_ingreso');
            $t->string('password_temp');

            $t->string('nivel_formacion')->nullable();
            $t->string('especialidad')->nullable();
            $t->enum('turno', ['mañana','tarde','noche'])->nullable();
            $t->date('fecha_ingreso')->nullable();

            $t->boolean('activo')->default(true);
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('teachers'); }
};
