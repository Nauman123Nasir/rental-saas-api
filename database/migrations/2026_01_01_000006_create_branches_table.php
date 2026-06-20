<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('code', 20)->nullable();          // Short branch code e.g. DXB-01
            $table->foreignId('country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->foreignId('timezone_id')->nullable()->constrained('timezones')->nullOnDelete();
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->string('address', 255)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('phone', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Ensure branch codes are unique within a tenant
            $table->unique(['tenant_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
