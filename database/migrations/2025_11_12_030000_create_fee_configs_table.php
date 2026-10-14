<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('fee_configs', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('year');
            $table->enum('nivel',['inicial','primaria','secundaria'])->nullable();
            $table->tinyInteger('grado')->nullable();
            $table->string('concepto'); // e.g., 'Matrícula', 'Mensualidad'
            $table->decimal('monto', 10, 2);
            $table->date('fecha_vencimiento')->nullable();
            $table->boolean('obligatorio')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('fee_configs');
    }
};
