<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->char('code', 3)->unique();   // ISO 4217 e.g. USD, AED
            $table->string('symbol', 10);
            $table->string('name', 100);
            $table->unsignedTinyInteger('decimal_places')->default(2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
