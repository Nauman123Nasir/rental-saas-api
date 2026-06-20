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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('reservation_no', 50)->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pickup_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('return_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->dateTime('pickup_datetime_utc');
            $table->dateTime('return_datetime_utc');
            $table->string('pickup_timezone', 100)->nullable();
            $table->string('return_timezone', 100)->nullable();
            $table->decimal('duration_hours', 10, 2)->default(0);
            $table->string('reservation_source', 50)->nullable();
            $table->string('status', 50)->default('Draft');
            $table->foreignId('currency_id')->nullable();
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('deposit_amount', 18, 2)->default(0);
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
