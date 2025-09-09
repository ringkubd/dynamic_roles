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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dynamic_permission_checks');
    }
};
