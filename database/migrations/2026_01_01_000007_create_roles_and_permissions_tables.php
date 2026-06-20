<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Roles are scoped to a tenant (each tenant defines their own roles)
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('description', 255)->nullable();
            $table->boolean('is_system')->default(false);   // system roles cannot be deleted
            $table->timestamps();

            $table->unique(['tenant_id', 'name']);
        });

        // Permissions are global (not tenant-scoped) — they define what actions exist
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('module', 60);    // e.g. customers, assets, rentals
            $table->string('action', 60);    // e.g. view, create, update, delete
            $table->string('description', 255)->nullable();
            $table->timestamps();

            $table->unique(['module', 'action']);
        });

        // Which permissions each role has
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        // Which roles each user has (a user may have multiple roles)
        Schema::create('user_roles', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->primary(['user_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
