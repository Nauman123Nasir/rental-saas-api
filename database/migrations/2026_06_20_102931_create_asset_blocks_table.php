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
        Schema::create('asset_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable(); // Depending on volume 9 table 22 vs 33
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->enum('block_type', ['Reservation', 'Rental', 'Maintenance', 'Compliance', 'Manual']);
            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime');
            $table->string('reason')->nullable();
            $table->string('reference_type')->nullable(); // Polimorphic relation reference
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('cost', 10, 2)->default(0); // I will add cost since we need it for maintenance logging per previous spec
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_blocks');
    }
};
