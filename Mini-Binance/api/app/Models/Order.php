<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'market',
        'side',
        'type',
        'price',
        'quantity',
        'quantity_filled',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:8',
            'quantity' => 'decimal:8',
            'quantity_filled' => 'decimal:8',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function buyTrades(): HasMany
    {
        return $this->hasMany(Trade::class, 'buy_order_id');
    }

    public function sellTrades(): HasMany
    {
        return $this->hasMany(Trade::class, 'sell_order_id');
    }

    public function remainingQuantity(): float
    {
        return $this->quantity - $this->quantity_filled;
    }

    public function isFilled(): bool
    {
        return $this->quantity_filled >= $this->quantity;
    }
}
