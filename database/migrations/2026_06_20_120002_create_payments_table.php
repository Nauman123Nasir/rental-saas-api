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
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('invoice_id')->index();
            $table->unsignedBigInteger('customer_id')->index();

            $table->string('payment_no')->unique();

            // Method: cash | card | bank_transfer | cheque | online
            $table->string('payment_method', 30)->default('cash');

            $table->decimal('amount', 14, 2);
            $table->string('currency_code', 5)->default('USD');

            $table->dateTime('payment_datetime_utc');

            $table->string('reference_no')->nullable();   // cheque no, txn id, etc.
            $table->text('notes')->nullable();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('invoice_id')->references('id')->on('invoices')->cascadeOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
