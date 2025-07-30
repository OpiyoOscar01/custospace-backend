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
        Schema::create('time_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->datetime('started_at');
            $table->datetime('ended_at')->nullable();
            $table->integer('duration')->nullable(); // in minutes
            $table->text('description')->nullable();
            $table->boolean('is_billable')->default(false);
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'started_at']);
            $table->index(['task_id', 'started_at']);
            $table->index(['started_at', 'ended_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_logs');
    }
};
