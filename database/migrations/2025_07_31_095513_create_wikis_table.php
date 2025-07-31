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
        Schema::create('wikis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('wikis')->nullOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->longText('content');
            $table->boolean('is_published')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->unique(['workspace_id', 'slug']);
            $table->index(['workspace_id', 'is_published']);
            $table->index(['parent_id', 'title']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wikis');
    }
};
