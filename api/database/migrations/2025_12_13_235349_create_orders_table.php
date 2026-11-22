<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('market', 20);
            $table->enum('side', ['buy', 'sell']);
            $table->enum('type', ['limit', 'market']);
            $table->decimal('price', 20, 8)->nullable();
            $table->decimal('quantity', 20, 8);
            $table->decimal('quantity_filled', 20, 8)->default(0);
            $table->enum('status', ['open', 'partial', 'filled', 'cancelled'])->default('open');
            $table->timestamps();
            
            $table->index(['market', 'side', 'status', 'price']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
