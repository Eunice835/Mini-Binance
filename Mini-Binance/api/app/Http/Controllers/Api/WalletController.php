<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Asset;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $wallets = $request->user()->wallets()->with('asset')->get();
        
        return response()->json($wallets);
    }

    public function deposit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'amount' => 'required|numeric|min:0.00000001',
        ]);

        $user = $request->user();

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'asset_id' => $validated['asset_id'],
            'type' => 'deposit',
            'amount' => $validated['amount'],
            'status' => 'pending',
        ]);

        AuditLog::log('wallet.deposit_requested', $user->id, 'Transaction', $transaction->id, [
            'amount' => $validated['amount'],
            'asset_id' => $validated['asset_id'],
        ]);

        return response()->json([
            'message' => 'Deposit request submitted',
            'transaction' => $transaction->load('asset'),
        ], 201);
    }

    public function withdraw(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'amount' => 'required|numeric|min:0.00000001',
        ]);

        $user = $request->user();
        $wallet = Wallet::where('user_id', $user->id)
            ->where('asset_id', $validated['asset_id'])
            ->first();

        if (!$wallet || $wallet->balance_available < $validated['amount']) {
            return response()->json(['message' => 'Insufficient balance'], 400);
        }

        $wallet->decrement('balance_available', $validated['amount']);

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'asset_id' => $validated['asset_id'],
            'type' => 'withdraw',
            'amount' => $validated['amount'],
            'status' => 'pending',
        ]);

        AuditLog::log('wallet.withdraw_requested', $user->id, 'Transaction', $transaction->id, [
            'amount' => $validated['amount'],
            'asset_id' => $validated['asset_id'],
        ]);

        return response()->json([
            'message' => 'Withdrawal request submitted',
            'transaction' => $transaction->load('asset'),
        ], 201);
    }

    public function transactions(Request $request): JsonResponse
    {
        $transactions = $request->user()
            ->transactions()
            ->with('asset')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($transactions);
    }
}
