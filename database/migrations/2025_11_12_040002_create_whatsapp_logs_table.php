<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('whatsapp_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('telefono');
            $table->text('mensaje');
            $table->enum('tipo',['recordatorio','alerta','informe'])->default('informe');
            $table->enum('estado',['enviado','fallido','pendiente'])->default('pendiente');
            $table->text('respuesta_api')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('whatsapp_logs');
    }
};
