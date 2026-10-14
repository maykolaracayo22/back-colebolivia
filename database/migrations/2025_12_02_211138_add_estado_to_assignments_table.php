<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up()
{
    Schema::table('assignments', function (Blueprint $table) {
        $table->string('estado', 20)->default('ACTIVO');
    });
}

public function down()
{
    Schema::table('assignments', function (Blueprint $table) {
        $table->dropColumn('estado');
    });
}

};
