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
        Schema::create('rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('rental_no', 50)->unique();
            $table->foreignId('reservation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('primary_driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pickup_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('return_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->dateTime('pickup_datetime_utc');
            $table->dateTime('expected_return_datetime_utc');
            $table->dateTime('actual_return_datetime_utc')->nullable();
            $table->string('status', 50); // Draft, Pending Pickup, Active, Extended, Overdue, Returned, Closed, Cancelled
            $table->foreignId('currency_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('exchange_rate', 18, 6)->nullable();
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('deposit_amount', 18, 2)->default(0);
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->decimal('balance_due', 18, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rentals');
    }
};
