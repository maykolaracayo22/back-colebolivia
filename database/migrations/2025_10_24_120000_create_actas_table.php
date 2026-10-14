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
        if (!Schema::hasTable('actas')) {
            Schema::create('actas', function (Blueprint $table) {
                $table->id();
                $table->integer('year');
                $table->string('nivel');
                $table->string('grado');
                $table->string('seccion');
                $table->foreignId('materia_id')->constrained('subjects');
                $table->string('materia_nombre');
                $table->foreignId('docente_id')->constrained('teachers');
                $table->string('docente_nombre');
                $table->integer('term')->comment('1, 2, 3'); // trimestre
                $table->enum('status', ['DRAFT', 'LOCKED', 'PUBLISHED'])->default('DRAFT');
                $table->dateTime('window_starts_at')->nullable();
                $table->dateTime('window_ends_at')->nullable();
                $table->json('metrics')->nullable();
                $table->string('etag')->nullable();
                $table->dateTime('locked_at')->nullable();
                $table->foreignId('locked_by')->nullable()->constrained('users');
                $table->dateTime('published_at')->nullable();
                $table->foreignId('published_by')->nullable()->constrained('users');
                $table->timestamps();
                $table->softDeletes();

                $table->index(['docente_id', 'materia_id', 'term', 'year']);
                $table->index(['status', 'window_ends_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('actas');
    }
};
