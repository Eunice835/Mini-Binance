<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Wallet extends Model
{
    protected $fillable = [
        'user_id',
        'asset_id',
        'balance_available',
        'balance_locked',
    ];

    protected function casts(): array
    {
        return [
            'balance_available' => 'decimal:8',
            'balance_locked' => 'decimal:8',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function totalBalance(): float
    {
        return $this->balance_available + $this->balance_locked;
    }
}
