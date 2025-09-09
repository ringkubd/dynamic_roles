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
        Schema::create(config('dynamic-roles.table_names.dynamic_menu_permissions', 'dynamic_menu_permissions'), function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('menu_id');
            $table->unsignedBigInteger('permission_id');
            $table->timestamps();

            $table->foreign('menu_id')->references('id')->on(config('dynamic-roles.table_names.dynamic_menus', 'dynamic_menus'))->onDelete('cascade');
            $table->foreign('permission_id')->references('id')->on(config('permission.table_names.permissions', 'permissions'))->onDelete('cascade');
            
            $table->unique(['menu_id', 'permission_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('dynamic-roles.table_names.dynamic_menu_permissions', 'dynamic_menu_permissions'));
    }
};
