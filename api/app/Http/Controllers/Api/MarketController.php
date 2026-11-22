<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Trade;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketController extends Controller
{
    public function orderbook(Request $request): JsonResponse
    {
        $market = $request->query('market', 'BTC-USDT');

        $bids = Order::where('market', $market)
            ->where('side', 'buy')
            ->whereIn('status', ['open', 'partial'])
            ->orderBy('price', 'desc')
            ->limit(50)
            ->get()
            ->groupBy('price')
            ->map(function ($orders) {
                return [
                    'price' => (float) $orders->first()->price,
                    'quantity' => $orders->sum(fn($o) => $o->quantity - $o->quantity_filled),
                    'count' => $orders->count(),
                ];
            })
            ->values();

        $asks = Order::where('market', $market)
            ->where('side', 'sell')
            ->whereIn('status', ['open', 'partial'])
            ->orderBy('price', 'asc')
            ->limit(50)
            ->get()
            ->groupBy('price')
            ->map(function ($orders) {
                return [
                    'price' => (float) $orders->first()->price,
                    'quantity' => $orders->sum(fn($o) => $o->quantity - $o->quantity_filled),
                    'count' => $orders->count(),
                ];
            })
            ->values();

        return response()->json([
            'market' => $market,
            'bids' => $bids,
            'asks' => $asks,
        ]);
    }

    public function trades(Request $request): JsonResponse
    {
        $market = $request->query('market', 'BTC-USDT');

        $trades = Trade::where('market', $market)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($trade) {
                return [
                    'id' => $trade->id,
                    'price' => (float) $trade->price,
                    'quantity' => (float) $trade->quantity,
                    'time' => $trade->created_at->toISOString(),
                ];
            });

        return response()->json([
            'market' => $market,
            'trades' => $trades,
        ]);
    }

    public function ticker(Request $request): JsonResponse
    {
        $market = $request->query('market', 'BTC-USDT');

        $lastTrade = Trade::where('market', $market)
            ->orderBy('created_at', 'desc')
            ->first();

        $trades24h = Trade::where('market', $market)
            ->where('created_at', '>=', now()->subDay())
            ->get();

        $volume = $trades24h->sum('quantity');
        $high = $trades24h->max('price') ?? 0;
        $low = $trades24h->min('price') ?? 0;

        return response()->json([
            'market' => $market,
            'last_price' => $lastTrade ? (float) $lastTrade->price : 50000.0,
            'volume_24h' => (float) $volume,
            'high_24h' => (float) $high,
            'low_24h' => (float) $low,
        ]);
    }
}
