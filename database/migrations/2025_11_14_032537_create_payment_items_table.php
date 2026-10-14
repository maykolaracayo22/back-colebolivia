<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->onDelete('cascade');
            $table->foreignId('charge_id')->constrained('account_charges')->onDelete('restrict');
            $table->decimal('amount', 10, 2);
            $table->timestamps();

            $table->index('payment_id');
            $table->index('charge_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_items');
    }
};
