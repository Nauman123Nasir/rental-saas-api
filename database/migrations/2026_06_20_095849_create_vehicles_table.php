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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('make');
            $table->string('model');
            $table->integer('year');
            $table->string('license_plate');
            $table->string('vin')->nullable();
            $table->string('color')->nullable();
            $table->string('category')->nullable();
            $table->enum('status', ['available', 'rented', 'maintenance', 'retired'])->default('available');
            $table->integer('mileage')->default(0);
            $table->decimal('daily_rate', 10, 2)->default(0);
            $table->decimal('weekly_rate', 10, 2)->default(0);
            $table->decimal('monthly_rate', 10, 2)->default(0);
            $table->decimal('hourly_rate', 10, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['tenant_id', 'license_plate']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
