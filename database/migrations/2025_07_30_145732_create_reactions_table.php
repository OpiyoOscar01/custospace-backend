<?php
// database/migrations/xxxx_xx_xx_create_reactions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('reactable');
            $table->string('type'); // emoji type like 'thumbs_up', 'heart', etc.
            $table->timestamps();
            
            $table->unique(['user_id', 'reactable_type', 'reactable_id', 'type']);
            $table->index(['reactable_type', 'reactable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reactions');
    }
};