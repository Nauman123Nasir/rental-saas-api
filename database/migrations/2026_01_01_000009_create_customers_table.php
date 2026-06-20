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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('customer_code');
            $table->string('type'); // 'Individual', 'Business'
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('company_name')->nullable();
            $table->string('email');
            $table->string('phone');
            $table->string('status')->default('active'); // 'active', 'inactive', 'suspended'
            $table->decimal('credit_limit', 12, 2)->default(0.00);
            $table->timestamps();
            $table->softDeletes();

            // Ensure customer_code is unique per tenant
            $table->unique(['tenant_id', 'customer_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
