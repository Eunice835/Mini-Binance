<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Order;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Asset::create(['symbol' => 'BTC', 'name' => 'Bitcoin', 'precision' => 8]);
        Asset::create(['symbol' => 'USDT', 'name' => 'Tether USD', 'precision' => 2]);
    }

    public function test_can_get_orderbook(): void
    {
        $user = User::factory()->create();

        Order::create([
            'user_id' => $user->id,
            'market' => 'BTC-USDT',
            'side' => 'buy',
            'type' => 'limit',
            'price' => 50000.00,
            'quantity' => 0.1,
            'quantity_filled' => 0,
            'status' => 'open',
        ]);

        Order::create([
            'user_id' => $user->id,
            'market' => 'BTC-USDT',
            'side' => 'sell',
            'type' => 'limit',
            'price' => 51000.00,
            'quantity' => 0.2,
            'quantity_filled' => 0,
            'status' => 'open',
        ]);

        $response = $this->getJson('/api/market/orderbook?market=BTC-USDT');

        $response->assertStatus(200)
            ->assertJsonStructure(['bids', 'asks']);
    }

    public function test_can_get_recent_trades(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $buyOrder = Order::create([
            'user_id' => $user1->id,
            'market' => 'BTC-USDT',
            'side' => 'buy',
            'type' => 'market',
            'price' => 50000.00,
            'quantity' => 0.1,
            'quantity_filled' => 0.1,
            'status' => 'filled',
        ]);

        $sellOrder = Order::create([
            'user_id' => $user2->id,
            'market' => 'BTC-USDT',
            'side' => 'sell',
            'type' => 'limit',
            'price' => 50000.00,
            'quantity' => 0.1,
            'quantity_filled' => 0.1,
            'status' => 'filled',
        ]);

        Trade::create([
            'market' => 'BTC-USDT',
            'buy_order_id' => $buyOrder->id,
            'sell_order_id' => $sellOrder->id,
            'taker_user_id' => $user1->id,
            'maker_user_id' => $user2->id,
            'price' => 50000.00,
            'quantity' => 0.1,
        ]);

        $response = $this->getJson('/api/market/trades?market=BTC-USDT');

        $response->assertStatus(200)
            ->assertJsonCount(1);
    }

    public function test_can_get_ticker(): void
    {
        $response = $this->getJson('/api/market/ticker?market=BTC-USDT');

        $response->assertStatus(200)
            ->assertJsonStructure(['market', 'last_price', 'volume_24h', 'high_24h', 'low_24h', 'change_24h']);
    }
}
