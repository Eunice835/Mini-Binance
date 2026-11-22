<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\Order;
use App\Models\Trade;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $assets = [
            ['symbol' => 'BTC', 'name' => 'Bitcoin', 'precision' => 8],
            ['symbol' => 'ETH', 'name' => 'Ethereum', 'precision' => 8],
            ['symbol' => 'USDT', 'name' => 'Tether USD', 'precision' => 2],
        ];

        foreach ($assets as $assetData) {
            Asset::firstOrCreate(['symbol' => $assetData['symbol']], $assetData);
        }

        $admin = User::firstOrCreate(
            ['email' => 'admin@exchange.local'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('admin123456'),
                'role' => 'admin',
                'kyc_status' => 'approved',
                'email_verified_at' => now(),
            ]
        );

        $user = User::firstOrCreate(
            ['email' => 'user@exchange.local'],
            [
                'name' => 'Demo User',
                'password' => Hash::make('user123456'),
                'role' => 'user',
                'kyc_status' => 'approved',
                'email_verified_at' => now(),
            ]
        );

        $userWith2FA = User::firstOrCreate(
            ['email' => 'secure@exchange.local'],
            [
                'name' => 'Secure User',
                'password' => Hash::make('secure123456'),
                'role' => 'user',
                'kyc_status' => 'approved',
                'email_verified_at' => now(),
                'totp_enabled' => true,
                'totp_secret' => 'JBSWY3DPEHPK3PXP',
            ]
        );

        $frozenUser = User::firstOrCreate(
            ['email' => 'frozen@exchange.local'],
            [
                'name' => 'Frozen User',
                'password' => Hash::make('frozen123456'),
                'role' => 'user',
                'kyc_status' => 'approved',
                'email_verified_at' => now(),
                'is_frozen' => true,
            ]
        );

        $traders = [];
        $traderNames = ['Alice Chen', 'Bob Smith', 'Carlos Garcia', 'Diana Lee', 'Erik Johnson', 'Fatima Ahmed', 'George Wilson', 'Hannah Kim'];
        foreach ($traderNames as $i => $name) {
            $traders[] = User::firstOrCreate(
                ['email' => 'trader' . ($i + 1) . '@exchange.local'],
                [
                    'name' => $name,
                    'password' => Hash::make('trader123456'),
                    'role' => 'user',
                    'kyc_status' => 'approved',
                    'email_verified_at' => now(),
                ]
            );
        }

        $allAssets = Asset::all();
        $allUsers = array_merge([$admin, $user, $userWith2FA, $frozenUser], $traders);

        foreach ($allUsers as $u) {
            foreach ($allAssets as $asset) {
                $balance = match ($asset->symbol) {
                    'BTC' => rand(5, 20) / 10,
                    'ETH' => rand(50, 200) / 10,
                    'USDT' => rand(5000, 50000),
                    default => 0,
                };

                if ($u->email === 'admin@exchange.local' || $u->email === 'user@exchange.local') {
                    $balance = match ($asset->symbol) {
                        'BTC' => 1.0,
                        'ETH' => 10.0,
                        'USDT' => 10000.0,
                        default => 0,
                    };
                }

                Wallet::firstOrCreate(
                    ['user_id' => $u->id, 'asset_id' => $asset->id],
                    ['balance_available' => $balance, 'balance_locked' => 0]
                );
            }
        }

        $this->seedOrderBook($traders);
        $this->seedTradeHistory($traders);
        $this->seedTransactions($user, $admin);
    }

    private function seedOrderBook(array $traders): void
    {
        $basePrice = 104250.00;

        $buyPrices = [
            104200.00, 104150.00, 104100.00, 104050.00, 104000.00,
            103950.00, 103900.00, 103800.00, 103700.00, 103500.00,
        ];

        $sellPrices = [
            104300.00, 104350.00, 104400.00, 104450.00, 104500.00,
            104600.00, 104700.00, 104800.00, 105000.00, 105500.00,
        ];

        foreach ($buyPrices as $i => $price) {
            $trader = $traders[$i % count($traders)];
            $quantity = round(rand(10, 100) / 1000, 4);
            
            Order::create([
                'user_id' => $trader->id,
                'market' => 'BTC-USDT',
                'side' => 'buy',
                'type' => 'limit',
                'price' => $price,
                'quantity' => $quantity,
                'quantity_filled' => 0,
                'status' => 'open',
                'created_at' => now()->subMinutes(rand(1, 60)),
            ]);
        }

        foreach ($sellPrices as $i => $price) {
            $trader = $traders[($i + 3) % count($traders)];
            $quantity = round(rand(10, 100) / 1000, 4);
            
            Order::create([
                'user_id' => $trader->id,
                'market' => 'BTC-USDT',
                'side' => 'sell',
                'type' => 'limit',
                'price' => $price,
                'quantity' => $quantity,
                'quantity_filled' => 0,
                'status' => 'open',
                'created_at' => now()->subMinutes(rand(1, 60)),
            ]);
        }
    }

    private function seedTradeHistory(array $traders): void
    {
        $prices = [
            104280.00, 104265.00, 104290.00, 104255.00, 104300.00,
            104245.00, 104270.00, 104285.00, 104260.00, 104275.00,
            104310.00, 104240.00, 104295.00, 104250.00, 104280.00,
        ];

        for ($i = 0; $i < 15; $i++) {
            $buyer = $traders[array_rand($traders)];
            $seller = $traders[array_rand($traders)];
            while ($seller->id === $buyer->id) {
                $seller = $traders[array_rand($traders)];
            }

            $quantity = round(rand(5, 50) / 1000, 4);
            $price = $prices[$i];

            $buyOrder = Order::create([
                'user_id' => $buyer->id,
                'market' => 'BTC-USDT',
                'side' => 'buy',
                'type' => 'market',
                'price' => $price,
                'quantity' => $quantity,
                'quantity_filled' => $quantity,
                'status' => 'filled',
                'created_at' => now()->subMinutes(rand(1, 120)),
            ]);

            $sellOrder = Order::create([
                'user_id' => $seller->id,
                'market' => 'BTC-USDT',
                'side' => 'sell',
                'type' => 'limit',
                'price' => $price,
                'quantity' => $quantity,
                'quantity_filled' => $quantity,
                'status' => 'filled',
                'created_at' => now()->subMinutes(rand(1, 120)),
            ]);

            Trade::create([
                'market' => 'BTC-USDT',
                'buy_order_id' => $buyOrder->id,
                'sell_order_id' => $sellOrder->id,
                'taker_user_id' => $buyer->id,
                'maker_user_id' => $seller->id,
                'price' => $price,
                'quantity' => $quantity,
                'created_at' => now()->subMinutes(rand(1, 120)),
            ]);
        }
    }

    private function seedTransactions(User $user, User $admin): void
    {
        $btc = Asset::where('symbol', 'BTC')->first();
        $eth = Asset::where('symbol', 'ETH')->first();
        $usdt = Asset::where('symbol', 'USDT')->first();

        $transactions = [
            ['user' => $user, 'asset' => $usdt, 'type' => 'deposit', 'amount' => 5000.00, 'status' => 'approved', 'days_ago' => 7],
            ['user' => $user, 'asset' => $usdt, 'type' => 'deposit', 'amount' => 3000.00, 'status' => 'approved', 'days_ago' => 5],
            ['user' => $user, 'asset' => $btc, 'type' => 'deposit', 'amount' => 0.5, 'status' => 'approved', 'days_ago' => 6],
            ['user' => $user, 'asset' => $eth, 'type' => 'deposit', 'amount' => 5.0, 'status' => 'approved', 'days_ago' => 4],
            ['user' => $user, 'asset' => $usdt, 'type' => 'withdraw', 'amount' => 1000.00, 'status' => 'approved', 'days_ago' => 2],
            ['user' => $user, 'asset' => $usdt, 'type' => 'deposit', 'amount' => 2500.00, 'status' => 'pending', 'days_ago' => 0],
            ['user' => $admin, 'asset' => $usdt, 'type' => 'deposit', 'amount' => 10000.00, 'status' => 'approved', 'days_ago' => 10],
            ['user' => $admin, 'asset' => $btc, 'type' => 'deposit', 'amount' => 1.0, 'status' => 'approved', 'days_ago' => 10],
        ];

        foreach ($transactions as $tx) {
            Transaction::create([
                'user_id' => $tx['user']->id,
                'asset_id' => $tx['asset']->id,
                'type' => $tx['type'],
                'amount' => $tx['amount'],
                'status' => $tx['status'],
                'created_at' => now()->subDays($tx['days_ago']),
                'updated_at' => now()->subDays($tx['days_ago']),
            ]);
        }
    }
}
