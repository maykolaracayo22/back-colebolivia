<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
    public function up(): void {
        Schema::create('periods', function (Blueprint $t) {
            $t->id();
            $t->string('year',4);
            $t->enum('code',['T1','T2','T3']);
            $t->date('inicio')->nullable();
            $t->date('fin')->nullable();
            $t->enum('estado',['ABIERTO','CERRADO'])->default('ABIERTO');
            $t->timestamps();

            $t->unique(['year','code']);
        });
    }
    public function down(): void { Schema::dropIfExists('periods'); }
};
