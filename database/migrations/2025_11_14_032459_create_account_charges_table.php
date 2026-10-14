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
        Schema::create('account_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_account_id')->constrained('student_accounts')->onDelete('cascade');
            $table->enum('concept', ['MATRICULA', 'MENSUALIDAD', 'OTRO']);
            $table->tinyInteger('month')->nullable();
            $table->year('year');
            $table->string('description');
            $table->decimal('amount', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->date('due_date');
            $table->enum('status', ['PENDING', 'PARTIAL', 'PAID', 'CANCELED'])->default('PENDING');
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();

            $table->index(['student_account_id', 'status']);
            $table->index(['concept', 'year']);
            $table->index('due_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_charges');
    }
};
