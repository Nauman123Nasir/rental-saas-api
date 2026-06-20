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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('asset_categories')->nullOnDelete();
            $table->string('asset_code');
            $table->string('name')->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->integer('year')->nullable();
            $table->string('vin_number')->nullable();
            $table->string('serial_number')->nullable();
            $table->enum('status', ['Available', 'Reserved', 'Rented', 'Maintenance', 'Inactive', 'Retired'])->default('Available');
            $table->string('ownership_type')->nullable();
            $table->integer('current_mileage')->default(0);
            $table->decimal('current_hours', 10, 2)->default(0);
            $table->string('fuel_type')->nullable();
            $table->string('transmission')->nullable();
            $table->decimal('daily_rate', 10, 2)->default(0);
            $table->decimal('weekly_rate', 10, 2)->default(0);
            $table->decimal('monthly_rate', 10, 2)->default(0);
            $table->decimal('hourly_rate', 10, 2)->default(0);
            $table->foreignId('currency_id')->nullable(); // Since we don't have currency migration yet, just an ID
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['tenant_id', 'asset_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
