<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id')->index();

            // Description of the line item (e.g. "Rental – Day Rate x 5 days")
            $table->string('description');

            // Type: rental_base | fuel_charge | damage_charge | late_fee | discount | tax | other
            $table->string('line_type', 30)->default('rental_base');

            $table->decimal('unit_price', 14, 2)->default(0);
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('total', 14, 2)->default(0);

            $table->foreign('invoice_id')->references('id')->on('invoices')->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_lines');
    }
};
