<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buy_order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('sell_order_id')->constrained('orders')->onDelete('cascade');
            $table->string('market', 20);
            $table->decimal('price', 20, 8);
            $table->decimal('quantity', 20, 8);
            $table->foreignId('taker_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('maker_user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['market', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trades');
    }
};
