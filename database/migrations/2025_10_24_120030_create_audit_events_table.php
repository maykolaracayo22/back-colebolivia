<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('audit_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('user_nombre')->nullable();
            $t->string('accion');
            $t->string('target')->nullable();
            $t->string('ref')->nullable();
            $t->json('before')->nullable();
            $t->json('after')->nullable();
            $t->timestamp('ts')->useCurrent();
            $t->string('ip')->nullable();
            $t->string('ua')->nullable();
            $t->timestamps();

            $t->index(['user_id','ts']);
        });
    }

    public function down(): void { Schema::dropIfExists('audit_events'); }
};
