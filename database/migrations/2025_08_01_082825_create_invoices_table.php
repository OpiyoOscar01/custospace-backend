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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('stripe_id')->unique();
            $table->string('number');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3);
            $table->enum('status', ['draft', 'open', 'paid', 'uncollectible', 'void'])->default('draft');
            $table->timestamp('due_date')->nullable();
            $table->json('line_items')->nullable();
            $table->timestamps();
            
            $table->index(['workspace_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
