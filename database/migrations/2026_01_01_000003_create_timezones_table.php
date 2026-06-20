<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timezones', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();   // e.g. Asia/Dubai
            $table->string('utc_offset', 10);        // e.g. +05:30
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timezones');
    }
};
