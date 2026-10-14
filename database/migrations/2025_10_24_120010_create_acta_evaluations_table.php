<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('acta_evaluations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('acta_id')->constrained('actas')->cascadeOnDelete();
            $t->string('nombre');
            $t->unsignedTinyInteger('peso')->default(0);
            $t->date('fecha')->nullable();
            $t->timestamps();

            $t->index(['acta_id']);
        });
    }

    public function down(): void { Schema::dropIfExists('acta_evaluations'); }
};
