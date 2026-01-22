<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Trade;
use App\Models\Wallet;
use App\Models\Asset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'market' => 'required|string|in:BTC-USDT',
            'side' => 'required|in:buy,sell',
            'type' => 'required|in:limit,market',
            'price' => 'required_if:type,limit|nullable|numeric|min:0.00000001',
            'quantity' => 'required|numeric|min:0.00000001',
        ]);

        $user = $request->user();

        if ($user->is_frozen) {
            return response()->json(['message' => 'Account is frozen'], 403);
        }

        [$baseAsset, $quoteAsset] = explode('-', $validated['market']);
        $baseAssetModel = Asset::where('symbol', $baseAsset)->first();
        $quoteAssetModel = Asset::where('symbol', $quoteAsset)->first();

        if (!$baseAssetModel || !$quoteAssetModel) {
            return response()->json(['message' => 'Invalid market'], 400);
        }

        $order = null;

        DB::transaction(function () use ($validated, $user, $baseAssetModel, $quoteAssetModel, &$order) {
            if ($validated['side'] === 'buy') {
                $quoteWallet = Wallet::where('user_id', $user->id)
                    ->where('asset_id', $quoteAssetModel->id)
                    ->lockForUpdate()
                    ->first();

                $price = $validated['type'] === 'market' 
                    ? $this->getMarketPrice($validated['market'], 'buy')
                    : $validated['price'];

                $totalCost = $validated['quantity'] * $price;

                if (!$quoteWallet || $quoteWallet->balance_available < $totalCost) {
                    throw new \Exception('Insufficient balance');
                }

                $quoteWallet->decrement('balance_available', $totalCost);
                $quoteWallet->increment('balance_locked', $totalCost);
            } else {
                $baseWallet = Wallet::where('user_id', $user->id)
                    ->where('asset_id', $baseAssetModel->id)
                    ->lockForUpdate()
                    ->first();

                if (!$baseWallet || $baseWallet->balance_available < $validated['quantity']) {
                    throw new \Exception('Insufficient balance');
                }

                $baseWallet->decrement('balance_available', $validated['quantity']);
                $baseWallet->increment('balance_locked', $validated['quantity']);
            }

            $order = Order::create([
                'user_id' => $user->id,
                'market' => $validated['market'],
                'side' => $validated['side'],
                'type' => $validated['type'],
                'price' => $validated['price'] ?? null,
                'quantity' => $validated['quantity'],
                'quantity_filled' => 0,
                'status' => 'open',
            ]);
        });

        if (!$order) {
            return response()->json(['message' => 'Insufficient balance'], 400);
        }

        $this->matchOrder($order);

        AuditLog::log('order.created', $user->id, 'Order', $order->id, [
            'market' => $validated['market'],
            'side' => $validated['side'],
            'type' => $validated['type'],
            'quantity' => $validated['quantity'],
        ]);

        return response()->json([
            'message' => 'Order placed successfully',
            'order' => $order->fresh(),
        ], 201);
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $order = Order::where('id', $id)->where('user_id', $user->id)->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if (!in_array($order->status, ['open', 'partial'])) {
            return response()->json(['message' => 'Order cannot be cancelled'], 400);
        }

        DB::transaction(function () use ($order, $user) {
            [$baseAsset, $quoteAsset] = explode('-', $order->market);
            $baseAssetModel = Asset::where('symbol', $baseAsset)->first();
            $quoteAssetModel = Asset::where('symbol', $quoteAsset)->first();

            $remainingQty = $order->quantity - $order->quantity_filled;

            if ($order->side === 'buy') {
                $quoteWallet = Wallet::where('user_id', $user->id)
                    ->where('asset_id', $quoteAssetModel->id)
                    ->lockForUpdate()
                    ->first();

                $lockedAmount = $remainingQty * ($order->price ?? 0);
                $quoteWallet->decrement('balance_locked', $lockedAmount);
                $quoteWallet->increment('balance_available', $lockedAmount);
            } else {
                $baseWallet = Wallet::where('user_id', $user->id)
                    ->where('asset_id', $baseAssetModel->id)
                    ->lockForUpdate()
                    ->first();

                $baseWallet->decrement('balance_locked', $remainingQty);
                $baseWallet->increment('balance_available', $remainingQty);
            }

            $order->update(['status' => 'cancelled']);
        });

        AuditLog::log('order.cancelled', $user->id, 'Order', $order->id);

        return response()->json(['message' => 'Order cancelled successfully']);
    }

    public function openOrders(Request $request): JsonResponse
    {
        $orders = $request->user()
            ->orders()
            ->whereIn('status', ['open', 'partial'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($orders);
    }

    public function history(Request $request): JsonResponse
    {
        $orders = $request->user()
            ->orders()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($orders);
    }

    public function trades(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        
        $trades = Trade::where('taker_user_id', $userId)
            ->orWhere('maker_user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($trades);
    }

    private function getMarketPrice(string $market, string $side): float
    {
        $oppositeOrder = Order::where('market', $market)
            ->where('side', $side === 'buy' ? 'sell' : 'buy')
            ->whereIn('status', ['open', 'partial'])
            ->orderBy('price', $side === 'buy' ? 'asc' : 'desc')
            ->first();

        return $oppositeOrder ? (float) $oppositeOrder->price : 50000.0;
    }

    private function matchOrder(Order $incomingOrder): void
    {
        $oppositeOrders = Order::where('market', $incomingOrder->market)
            ->where('side', $incomingOrder->side === 'buy' ? 'sell' : 'buy')
            ->whereIn('status', ['open', 'partial'])
            ->orderBy('price', $incomingOrder->side === 'buy' ? 'asc' : 'desc')
            ->orderBy('created_at', 'asc')
            ->get();

        [$baseAsset, $quoteAsset] = explode('-', $incomingOrder->market);
        $baseAssetModel = Asset::where('symbol', $baseAsset)->first();
        $quoteAssetModel = Asset::where('symbol', $quoteAsset)->first();

        foreach ($oppositeOrders as $makerOrder) {
            if ($incomingOrder->quantity_filled >= $incomingOrder->quantity) {
                break;
            }

            if ($incomingOrder->type === 'limit') {
                if ($incomingOrder->side === 'buy' && $makerOrder->price > $incomingOrder->price) {
                    break;
                }
                if ($incomingOrder->side === 'sell' && $makerOrder->price < $incomingOrder->price) {
                    break;
                }
            }

            DB::transaction(function () use ($incomingOrder, $makerOrder, $baseAssetModel, $quoteAssetModel) {
                $incomingRemaining = $incomingOrder->quantity - $incomingOrder->quantity_filled;
                $makerRemaining = $makerOrder->quantity - $makerOrder->quantity_filled;
                $matchQty = min($incomingRemaining, $makerRemaining);
                $matchPrice = (float) $makerOrder->price;

                Trade::create([
                    'buy_order_id' => $incomingOrder->side === 'buy' ? $incomingOrder->id : $makerOrder->id,
                    'sell_order_id' => $incomingOrder->side === 'sell' ? $incomingOrder->id : $makerOrder->id,
                    'market' => $incomingOrder->market,
                    'price' => $matchPrice,
                    'quantity' => $matchQty,
                    'taker_user_id' => $incomingOrder->user_id,
                    'maker_user_id' => $makerOrder->user_id,
                ]);

                $incomingOrder->increment('quantity_filled', $matchQty);
                $makerOrder->increment('quantity_filled', $matchQty);

                if ($incomingOrder->side === 'buy') {
                    $buyerBaseWallet = Wallet::where('user_id', $incomingOrder->user_id)
                        ->where('asset_id', $baseAssetModel->id)->first();
                    $buyerQuoteWallet = Wallet::where('user_id', $incomingOrder->user_id)
                        ->where('asset_id', $quoteAssetModel->id)->first();
                    $sellerBaseWallet = Wallet::where('user_id', $makerOrder->user_id)
                        ->where('asset_id', $baseAssetModel->id)->first();
                    $sellerQuoteWallet = Wallet::where('user_id', $makerOrder->user_id)
                        ->where('asset_id', $quoteAssetModel->id)->first();

                    $cost = $matchQty * $matchPrice;
                    $buyerQuoteWallet->decrement('balance_locked', $cost);
                    $buyerBaseWallet->increment('balance_available', $matchQty);
                    $sellerBaseWallet->decrement('balance_locked', $matchQty);
                    $sellerQuoteWallet->increment('balance_available', $cost);
                } else {
                    $sellerBaseWallet = Wallet::where('user_id', $incomingOrder->user_id)
                        ->where('asset_id', $baseAssetModel->id)->first();
                    $sellerQuoteWallet = Wallet::where('user_id', $incomingOrder->user_id)
                        ->where('asset_id', $quoteAssetModel->id)->first();
                    $buyerBaseWallet = Wallet::where('user_id', $makerOrder->user_id)
                        ->where('asset_id', $baseAssetModel->id)->first();
                    $buyerQuoteWallet = Wallet::where('user_id', $makerOrder->user_id)
                        ->where('asset_id', $quoteAssetModel->id)->first();

                    $cost = $matchQty * $matchPrice;
                    $sellerBaseWallet->decrement('balance_locked', $matchQty);
                    $sellerQuoteWallet->increment('balance_available', $cost);
                    $buyerQuoteWallet->decrement('balance_locked', $cost);
                    $buyerBaseWallet->increment('balance_available', $matchQty);
                }

                $incomingOrder->update([
                    'status' => $incomingOrder->quantity_filled >= $incomingOrder->quantity ? 'filled' : 'partial',
                ]);
                $makerOrder->update([
                    'status' => $makerOrder->quantity_filled >= $makerOrder->quantity ? 'filled' : 'partial',
                ]);
            });
        }
    }
}
