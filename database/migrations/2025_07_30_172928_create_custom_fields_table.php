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
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('key');
            $table->enum('type', ['text', 'number', 'date', 'select', 'multiselect', 'checkbox', 'textarea', 'url', 'email']);
            $table->string('applies_to'); // tasks, projects, users, etc.
            $table->json('options')->nullable(); // For select/multiselect
            $table->boolean('is_required')->default(false);
            $table->integer('order')->default(0);
            $table->timestamps();
            
            $table->unique(['workspace_id', 'applies_to', 'key']);
            $table->index(['workspace_id', 'applies_to']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_fields');
    }
};
