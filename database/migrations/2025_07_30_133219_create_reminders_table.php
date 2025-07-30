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
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('remindable');
            $table->datetime('remind_at');
            $table->enum('type', ['email', 'in_app', 'sms'])->default('in_app');
            $table->boolean('is_sent')->default(false);
            $table->timestamps();
            
            $table->index(['user_id', 'remind_at']);
            $table->index(['remindable_type', 'remindable_id']);
            $table->index(['remind_at', 'is_sent']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reminders');
    }
};
