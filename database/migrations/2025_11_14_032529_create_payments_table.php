<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_account_id')->constrained('student_accounts')->onDelete('cascade');
            $table->timestamp('payment_date');
            $table->enum('method', ['EFECTIVO', 'TRANSFERENCIA', 'QR', 'TARJETA']);
            $table->string('reference')->nullable();
            $table->decimal('total_amount', 10, 2);
            $table->foreignId('received_by')->constrained('users')->onDelete('restrict');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('student_account_id');
            $table->index('payment_date');
            $table->index('method');
            $table->index('reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
