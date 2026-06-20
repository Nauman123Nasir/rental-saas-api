<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->char('iso2', 2)->unique();
            $table->char('iso3', 3)->unique();
            $table->string('phone_code', 10)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
