<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Order;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Asset $btc;
    protected Asset $usdt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->btc = Asset::create(['symbol' => 'BTC', 'name' => 'Bitcoin', 'precision' => 8]);
        $this->usdt = Asset::create(['symbol' => 'USDT', 'name' => 'Tether USD', 'precision' => 2]);

        $this->user = User::factory()->create(['kyc_status' => 'approved']);

        Wallet::create([
            'user_id' => $this->user->id,
            'asset_id' => $this->btc->id,
            'balance_available' => 1.0,
            'balance_locked' => 0,
        ]);

        Wallet::create([
            'user_id' => $this->user->id,
            'asset_id' => $this->usdt->id,
            'balance_available' => 10000.0,
            'balance_locked' => 0,
        ]);
    }

    public function test_user_can_place_limit_buy_order(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/orders', [
                'market' => 'BTC-USDT',
                'side' => 'buy',
                'type' => 'limit',
                'price' => 50000.00,
                'quantity' => 0.1,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['order' => ['id', 'market', 'side', 'type', 'price', 'quantity', 'status']]);

        $this->assertDatabaseHas('orders', [
            'user_id' => $this->user->id,
            'market' => 'BTC-USDT',
            'side' => 'buy',
            'price' => 50000.00,
            'quantity' => 0.1,
            'status' => 'open',
        ]);
    }

    public function test_user_can_place_limit_sell_order(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/orders', [
                'market' => 'BTC-USDT',
                'side' => 'sell',
                'type' => 'limit',
                'price' => 55000.00,
                'quantity' => 0.5,
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('orders', [
            'user_id' => $this->user->id,
            'side' => 'sell',
            'price' => 55000.00,
            'quantity' => 0.5,
        ]);
    }

    public function test_user_cannot_place_order_without_sufficient_balance(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/orders', [
                'market' => 'BTC-USDT',
                'side' => 'buy',
                'type' => 'limit',
                'price' => 50000.00,
                'quantity' => 10.0,
            ]);

        $response->assertStatus(422);
    }

    public function test_user_can_cancel_open_order(): void
    {
        $order = Order::create([
            'user_id' => $this->user->id,
            'market' => 'BTC-USDT',
            'side' => 'buy',
            'type' => 'limit',
            'price' => 50000.00,
            'quantity' => 0.1,
            'quantity_filled' => 0,
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/orders/{$order->id}");

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_user_cannot_cancel_other_users_order(): void
    {
        $otherUser = User::factory()->create();
        $order = Order::create([
            'user_id' => $otherUser->id,
            'market' => 'BTC-USDT',
            'side' => 'buy',
            'type' => 'limit',
            'price' => 50000.00,
            'quantity' => 0.1,
            'quantity_filled' => 0,
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/orders/{$order->id}");

        $response->assertStatus(403);
    }

    public function test_user_can_get_open_orders(): void
    {
        Order::create([
            'user_id' => $this->user->id,
            'market' => 'BTC-USDT',
            'side' => 'buy',
            'type' => 'limit',
            'price' => 50000.00,
            'quantity' => 0.1,
            'quantity_filled' => 0,
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/orders/open');

        $response->assertStatus(200)
            ->assertJsonCount(1);
    }

    public function test_user_can_get_order_history(): void
    {
        Order::create([
            'user_id' => $this->user->id,
            'market' => 'BTC-USDT',
            'side' => 'buy',
            'type' => 'limit',
            'price' => 50000.00,
            'quantity' => 0.1,
            'quantity_filled' => 0.1,
            'status' => 'filled',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/orders/history');

        $response->assertStatus(200)
            ->assertJsonCount(1);
    }

    public function test_frozen_user_cannot_place_orders(): void
    {
        $this->user->update(['is_frozen' => true]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/orders', [
                'market' => 'BTC-USDT',
                'side' => 'buy',
                'type' => 'limit',
                'price' => 50000.00,
                'quantity' => 0.1,
            ]);

        $response->assertStatus(403);
    }
}
