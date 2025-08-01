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
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invited_by_id')->constrained('users')->cascadeOnDelete();
            $table->string('email');
            $table->string('token')->unique();
            $table->enum('role', ['owner', 'admin', 'member', 'viewer'])->default('member');
            $table->enum('status', ['pending', 'accepted', 'declined', 'expired'])->default('pending');
            $table->json('metadata')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
            
            $table->index(['workspace_id', 'status']);
            $table->index(['email', 'status']);
            $table->index(['token', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};