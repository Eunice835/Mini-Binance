<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletTest extends TestCase
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

    public function test_user_can_view_wallets(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/wallets');

        $response->assertStatus(200)
            ->assertJsonCount(2);
    }

    public function test_user_can_request_deposit(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/transactions/deposit', [
                'asset' => 'USDT',
                'amount' => 5000.00,
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'asset_id' => $this->usdt->id,
            'type' => 'deposit',
            'amount' => 5000.00,
            'status' => 'pending',
        ]);
    }

    public function test_user_can_request_withdrawal(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/transactions/withdraw', [
                'asset' => 'USDT',
                'amount' => 1000.00,
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'type' => 'withdraw',
            'amount' => 1000.00,
            'status' => 'pending',
        ]);
    }

    public function test_user_cannot_withdraw_more_than_available_balance(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/transactions/withdraw', [
                'asset' => 'USDT',
                'amount' => 50000.00,
            ]);

        $response->assertStatus(422);
    }

    public function test_user_can_view_transaction_history(): void
    {
        Transaction::create([
            'user_id' => $this->user->id,
            'asset_id' => $this->usdt->id,
            'type' => 'deposit',
            'amount' => 5000.00,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/transactions');

        $response->assertStatus(200)
            ->assertJsonCount(1);
    }

    public function test_frozen_user_cannot_withdraw(): void
    {
        $this->user->update(['is_frozen' => true]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/transactions/withdraw', [
                'asset' => 'USDT',
                'amount' => 100.00,
            ]);

        $response->assertStatus(403);
    }

    public function test_user_without_kyc_cannot_withdraw(): void
    {
        $this->user->update(['kyc_status' => 'none']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/transactions/withdraw', [
                'asset' => 'USDT',
                'amount' => 100.00,
            ]);

        $response->assertStatus(403);
    }
}
