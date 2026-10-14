<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('students', function (Blueprint $table) {
            $table->string('codigo_rude')->nullable()->unique()->after('rude');
            $table->string('nacionalidad')->nullable()->after('mes_ingreso');
            $table->string('dep_nac')->nullable()->after('nacionalidad');
            $table->string('muni_nac')->nullable()->after('dep_nac');
            $table->text('direccion')->nullable()->after('telefono');
            $table->string('pueblo_indigena')->nullable()->after('direccion');
            $table->boolean('discapacidad')->default(false)->after('pueblo_indigena');
            $table->json('idiomas')->nullable()->after('discapacidad');
            $table->boolean('vulnerabilidad')->default(false)->after('idiomas');
            $table->string('tutor_nombre')->nullable()->after('vulnerabilidad');
            $table->string('tutor_ci')->nullable()->after('tutor_nombre');
            $table->string('tutor_parentesco')->nullable()->after('tutor_ci');
            $table->string('tutor_telefono')->nullable()->after('tutor_parentesco');
            $table->integer('year')->nullable()->after('fecha_nac');
            $table->tinyInteger('grado')->nullable()->after('curso');
            $table->string('seccion',5)->nullable()->after('paralelo');
        });
    }

    public function down(): void {
        if (!Schema::hasTable('students')) {
            return;
        }
        
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'codigo_rude', 'nacionalidad', 'dep_nac', 'muni_nac',
                'direccion', 'pueblo_indigena', 'discapacidad', 'idiomas', 'vulnerabilidad',
                'tutor_nombre', 'tutor_ci', 'tutor_parentesco', 'tutor_telefono',
                'year', 'grado', 'seccion'
            ]);
        });
    }
};
