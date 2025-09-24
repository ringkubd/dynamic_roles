<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Combined Dynamic Roles Migration
 * 
 * This migration creates all the required tables for the Dynamic Roles package:
 * - dynamic_urls: Store URL patterns and their metadata
 * - dynamic_url_permissions: Link URLs to permissions
 * - dynamic_role_urls: Link URLs directly to roles
 * - dynamic_permission_checks: Log permission check attempts
 * - dynamic_menus: Hierarchical menu structure
 * - dynamic_menu_permissions: Link menus to permissions
 * - dynamic_menu_roles: Link menus to roles
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create dynamic_urls table
        Schema::create('dynamic_urls', function (Blueprint $table) {
            $table->id();
            $table->string('url');
            $table->string('method', 10)->default('GET');
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->string('controller')->nullable();
            $table->string('action')->nullable();
            $table->json('middleware')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_discovered')->default(false);
            $table->string('category', 100)->default('api');
            $table->integer('priority')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['url', 'method']);
            $table->index('is_active');
            $table->index('category');
            $table->index('auto_discovered');
            
            // Unique constraint for URL + method combination
            $table->unique(['url', 'method'], 'unique_url_method');
        });

        // 2. Create dynamic_url_permissions table
        Schema::create('dynamic_url_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dynamic_url_id')->constrained('dynamic_urls')->onDelete('cascade');
            $table->foreignId('permission_id')->constrained('permissions')->onDelete('cascade');
            $table->timestamps();

            // Unique constraint to prevent duplicate assignments
            $table->unique(['dynamic_url_id', 'permission_id'], 'unique_url_permission');
            
            // Indexes
            $table->index('dynamic_url_id');
            $table->index('permission_id');
        });

        // 3. Create dynamic_role_urls table
        Schema::create('dynamic_role_urls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dynamic_url_id')->constrained('dynamic_urls')->onDelete('cascade');
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->timestamps();

            // Unique constraint to prevent duplicate assignments
            $table->unique(['dynamic_url_id', 'role_id'], 'unique_url_role');
            
            // Indexes
            $table->index('dynamic_url_id');
            $table->index('role_id');
        });

        // 4. Create dynamic_permission_checks table
        Schema::create('dynamic_permission_checks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreignId('dynamic_url_id')->nullable()->constrained('dynamic_urls')->onDelete('cascade');
            $table->string('permission_name')->nullable();
            $table->boolean('granted')->default(false);
            $table->string('reason')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();

            // Indexes for performance
            $table->index('user_id');
            $table->index('dynamic_url_id');
            $table->index('granted');
            $table->index('checked_at');
            $table->index(['user_id', 'checked_at']);
            
            // Foreign key constraint for user_id (if users table exists)
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        // 5. Create dynamic_menus table
        Schema::create(config('dynamic-roles.table_names.dynamic_menus', 'dynamic_menus'), function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('label');
            $table->string('url')->nullable();
            $table->string('icon')->nullable();
            $table->string('route_name')->nullable();
            $table->json('route_params')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_visible')->default(true);
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // Additional custom data
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on(config('dynamic-roles.table_names.dynamic_menus', 'dynamic_menus'))->onDelete('cascade');
            $table->index(['parent_id', 'sort_order']);
            $table->index(['is_active', 'is_visible']);
            $table->unique(['name']);
        });

        // 6. Create dynamic_menu_permissions table
        Schema::create(config('dynamic-roles.table_names.dynamic_menu_permissions', 'dynamic_menu_permissions'), function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('menu_id');
            $table->unsignedBigInteger('permission_id');
            $table->timestamps();

            $table->foreign('menu_id')->references('id')->on(config('dynamic-roles.table_names.dynamic_menus', 'dynamic_menus'))->onDelete('cascade');
            $table->foreign('permission_id')->references('id')->on(config('permission.table_names.permissions', 'permissions'))->onDelete('cascade');
            
            $table->unique(['menu_id', 'permission_id']);
        });

        // 7. Create dynamic_menu_roles table
        Schema::create(config('dynamic-roles.table_names.dynamic_menu_roles', 'dynamic_menu_roles'), function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('menu_id');
            $table->unsignedBigInteger('role_id');
            $table->timestamps();

            $table->foreign('menu_id')->references('id')->on(config('dynamic-roles.table_names.dynamic_menus', 'dynamic_menus'))->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on(config('permission.table_names.roles', 'roles'))->onDelete('cascade');
            
            $table->unique(['menu_id', 'role_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop tables in reverse order to handle foreign key constraints
        Schema::dropIfExists(config('dynamic-roles.table_names.dynamic_menu_roles', 'dynamic_menu_roles'));
        Schema::dropIfExists(config('dynamic-roles.table_names.dynamic_menu_permissions', 'dynamic_menu_permissions'));
        Schema::dropIfExists(config('dynamic-roles.table_names.dynamic_menus', 'dynamic_menus'));
        Schema::dropIfExists('dynamic_permission_checks');
        Schema::dropIfExists('dynamic_role_urls');
        Schema::dropIfExists('dynamic_url_permissions');
        Schema::dropIfExists('dynamic_urls');
    }
};