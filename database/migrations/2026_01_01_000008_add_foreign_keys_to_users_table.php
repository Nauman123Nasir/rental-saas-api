<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add foreign key constraints to users that reference tenants & branches.
     * This runs AFTER tenants and branches are created.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('tenant_id')
                  ->references('id')
                  ->on('tenants')
                  ->nullOnDelete();

            $table->foreign('branch_id')
                  ->references('id')
                  ->on('branches')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropForeign(['branch_id']);
        });
    }
};
