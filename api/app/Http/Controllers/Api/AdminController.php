<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\KycDocument;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function users(Request $request): JsonResponse
    {
        $users = User::with('wallets.asset')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($users);
    }

    public function freezeUser(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->update(['is_frozen' => true]);

        AuditLog::log('admin.user_frozen', $request->user()->id, 'User', $id);

        return response()->json(['message' => 'User frozen successfully']);
    }

    public function unfreezeUser(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->update(['is_frozen' => false]);

        AuditLog::log('admin.user_unfrozen', $request->user()->id, 'User', $id);

        return response()->json(['message' => 'User unfrozen successfully']);
    }

    public function approveKyc(Request $request, int $id): JsonResponse
    {
        $document = KycDocument::findOrFail($id);
        
        $document->update([
            'status' => 'approved',
            'reviewer_id' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $document->user->update(['kyc_status' => 'approved']);

        AuditLog::log('admin.kyc_approved', $request->user()->id, 'KycDocument', $id);

        return response()->json(['message' => 'KYC approved successfully']);
    }

    public function rejectKyc(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'required|string|max:500',
        ]);

        $document = KycDocument::findOrFail($id);
        
        $document->update([
            'status' => 'rejected',
            'reviewer_id' => $request->user()->id,
            'reviewed_at' => now(),
            'notes' => $validated['notes'],
        ]);

        $document->user->update(['kyc_status' => 'rejected']);

        AuditLog::log('admin.kyc_rejected', $request->user()->id, 'KycDocument', $id);

        return response()->json(['message' => 'KYC rejected']);
    }

    public function pendingKyc(Request $request): JsonResponse
    {
        $documents = KycDocument::where('status', 'pending')
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->paginate(20);

        return response()->json($documents);
    }

    public function creditUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'asset_id' => 'required|exists:assets,id',
            'amount' => 'required|numeric|min:0.00000001',
        ]);

        $wallet = Wallet::firstOrCreate(
            ['user_id' => $validated['user_id'], 'asset_id' => $validated['asset_id']],
            ['balance_available' => 0, 'balance_locked' => 0]
        );

        $wallet->increment('balance_available', $validated['amount']);

        AuditLog::log('admin.credit', $request->user()->id, 'Wallet', $wallet->id, [
            'user_id' => $validated['user_id'],
            'asset_id' => $validated['asset_id'],
            'amount' => $validated['amount'],
        ]);

        return response()->json(['message' => 'User credited successfully']);
    }

    public function debitUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'asset_id' => 'required|exists:assets,id',
            'amount' => 'required|numeric|min:0.00000001',
        ]);

        $wallet = Wallet::where('user_id', $validated['user_id'])
            ->where('asset_id', $validated['asset_id'])
            ->first();

        if (!$wallet || $wallet->balance_available < $validated['amount']) {
            return response()->json(['message' => 'Insufficient balance'], 400);
        }

        $wallet->decrement('balance_available', $validated['amount']);

        AuditLog::log('admin.debit', $request->user()->id, 'Wallet', $wallet->id, [
            'user_id' => $validated['user_id'],
            'asset_id' => $validated['asset_id'],
            'amount' => $validated['amount'],
        ]);

        return response()->json(['message' => 'User debited successfully']);
    }

    public function pendingTransactions(Request $request): JsonResponse
    {
        $transactions = Transaction::where('status', 'pending')
            ->with(['user', 'asset'])
            ->orderBy('created_at', 'asc')
            ->paginate(20);

        return response()->json($transactions);
    }

    public function approveTransaction(Request $request, int $id): JsonResponse
    {
        $transaction = Transaction::findOrFail($id);

        if ($transaction->status !== 'pending') {
            return response()->json(['message' => 'Transaction already processed'], 400);
        }

        if ($transaction->type === 'deposit') {
            $wallet = Wallet::where('user_id', $transaction->user_id)
                ->where('asset_id', $transaction->asset_id)
                ->first();
            
            $wallet->increment('balance_available', $transaction->amount);
        }

        $transaction->update([
            'status' => 'approved',
            'processor_id' => $request->user()->id,
            'processed_at' => now(),
        ]);

        AuditLog::log('admin.transaction_approved', $request->user()->id, 'Transaction', $id);

        return response()->json(['message' => 'Transaction approved']);
    }

    public function rejectTransaction(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $transaction = Transaction::findOrFail($id);

        if ($transaction->status !== 'pending') {
            return response()->json(['message' => 'Transaction already processed'], 400);
        }

        if ($transaction->type === 'withdraw') {
            $wallet = Wallet::where('user_id', $transaction->user_id)
                ->where('asset_id', $transaction->asset_id)
                ->first();
            
            $wallet->increment('balance_available', $transaction->amount);
        }

        $transaction->update([
            'status' => 'rejected',
            'processor_id' => $request->user()->id,
            'processed_at' => now(),
            'notes' => $validated['notes'] ?? null,
        ]);

        AuditLog::log('admin.transaction_rejected', $request->user()->id, 'Transaction', $id);

        return response()->json(['message' => 'Transaction rejected']);
    }

    public function auditLogs(Request $request): JsonResponse
    {
        $logs = AuditLog::with('actor')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json($logs);
    }
}
