<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('acta_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acta_id')->constrained('actas');
            $table->enum('categoria', ['SER', 'SABER', 'HACER', 'DECIDIR']);
            $table->string('code')->comment('ej: ser_1, saber_2');
            $table->string('label')->comment('nombre del criterio');
            $table->integer('max_points')->default(5);
            $table->timestamps();

            $table->unique(['acta_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acta_criteria');
    }
};
