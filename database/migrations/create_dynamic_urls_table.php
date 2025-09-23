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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dynamic_urls');
    }
};
