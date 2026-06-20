<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 150);
            $table->string('subdomain', 100)->unique()->nullable();
            $table->enum('status', ['active', 'suspended', 'trial', 'cancelled'])->default('trial');
            $table->foreignId('subscription_plan_id')
                  ->nullable()
                  ->constrained('subscription_plans')
                  ->nullOnDelete();
            $table->foreignId('currency_id')
                  ->nullable()
                  ->constrained('currencies')
                  ->nullOnDelete();
            $table->foreignId('timezone_id')
                  ->nullable()
                  ->constrained('timezones')
                  ->nullOnDelete();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
