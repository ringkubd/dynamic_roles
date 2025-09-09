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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dynamic_url_permissions');
    }
};
