<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('assignments', function (Blueprint $table) {
            // Si antes era tinyInteger/enum/string corto, lo cambiamos a string normal
            $table->string('dia', 20)->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('assignments', function (Blueprint $table) {
            // Aquí pon cómo estaba antes (ejemplo):
            // $table->tinyInteger('dia')->nullable()->change();
        });
    }
};
