<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('student_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('fee_config_id')->constrained()->onDelete('cascade');
            $table->decimal('monto', 10, 2);
            $table->enum('estado',['pendiente','pagado','anulado'])->default('pendiente');
            $table->date('fecha_vencimiento');
            $table->date('fecha_pago')->nullable();
            $table->decimal('monto_pagado', 10, 2)->nullable();
            $table->string('metodo_pago')->nullable(); // efectivo, transferencia, etc.
            $table->text('observaciones')->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('student_fees');
    }
};
