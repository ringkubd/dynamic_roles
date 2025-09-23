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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('dynamic-roles.table_names.dynamic_menus', 'dynamic_menus'));
    }
};
