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
        Schema::create('reservation_deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->string('deposit_type', 50); // Cash, Card Hold, Online Payment, Corporate Guarantee
            $table->decimal('amount', 18, 2)->default(0);
            $table->foreignId('currency_id')->nullable();
            $table->string('status', 50)->default('Pending'); // Pending, Collected, Released, Forfeited
            $table->string('transaction_reference', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservation_deposits');
    }
};
