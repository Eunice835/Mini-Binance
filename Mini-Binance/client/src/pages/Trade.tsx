import { useEffect, useState } from 'react';
import api from '../lib/api';
import type { Orderbook, Trade as TradeType, Wallet } from '../types';
import { ArrowUpRight, ArrowDownRight, RefreshCw } from 'lucide-react';

export default function Trade() {
  const [orderbook, setOrderbook] = useState<Orderbook | null>(null);
  const [recentTrades, setRecentTrades] = useState<TradeType[]>([]);
  const [wallets, setWallets] = useState<Wallet[]>([]);
  const [ticker, setTicker] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  
  const [side, setSide] = useState<'buy' | 'sell'>('buy');
  const [orderType, setOrderType] = useState<'limit' | 'market'>('limit');
  const [price, setPrice] = useState('');
  const [quantity, setQuantity] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [message, setMessage] = useState({ type: '', text: '' });

  const fetchData = async () => {
    try {
      const [orderbookRes, tradesRes, walletsRes, tickerRes] = await Promise.all([
        api.get('/market/orderbook?market=BTC-USDT'),
        api.get('/market/trades?market=BTC-USDT'),
        api.get('/wallets'),
        api.get('/market/ticker?market=BTC-USDT'),
      ]);
      setOrderbook(orderbookRes.data);
      setRecentTrades(tradesRes.data.trades);
      setWallets(walletsRes.data);
      setTicker(tickerRes.data);
    } catch (e) {
      console.error('Failed to fetch market data:', e);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchData();
    const interval = setInterval(fetchData, 5000);
    return () => clearInterval(interval);
  }, []);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSubmitting(true);
    setMessage({ type: '', text: '' });

    try {
      await api.post('/orders', {
        market: 'BTC-USDT',
        side,
        type: orderType,
        price: orderType === 'limit' ? parseFloat(price) : undefined,
        quantity: parseFloat(quantity),
      });
      setMessage({ type: 'success', text: 'Order placed successfully!' });
      setPrice('');
      setQuantity('');
      fetchData();
    } catch (err: any) {
      setMessage({ 
        type: 'error', 
        text: err.response?.data?.message || 'Failed to place order' 
      });
    } finally {
      setSubmitting(false);
    }
  };

  const btcWallet = wallets.find(w => w.asset.symbol === 'BTC');
  const usdtWallet = wallets.find(w => w.asset.symbol === 'USDT');

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-white">BTC / USDT</h1>
          {ticker && (
            <p className="text-3xl font-bold text-green-500 mt-1">
              ${ticker.last_price.toLocaleString()}
            </p>
          )}
        </div>
        <button 
          onClick={fetchData}
          className="p-2 bg-gray-700 rounded-lg hover:bg-gray-600"
        >
          <RefreshCw size={20} className="text-gray-400" />
        </button>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="lg:col-span-2 grid grid-cols-2 gap-4">
          <div className="bg-gray-800 rounded-lg p-4">
            <h3 className="text-sm font-medium text-gray-400 mb-3">Order Book - Bids</h3>
            <div className="space-y-1">
              <div className="flex justify-between text-xs text-gray-500 pb-2 border-b border-gray-700">
                <span>Price (USDT)</span>
                <span>Amount (BTC)</span>
              </div>
              {orderbook?.bids.slice(0, 10).map((bid, i) => (
                <div key={i} className="flex justify-between text-sm relative">
                  <div 
                    className="absolute inset-0 bg-green-500/10" 
                    style={{ width: `${Math.min(bid.quantity * 20, 100)}%` }}
                  />
                  <span className="text-green-500 relative z-10">
                    {bid.price.toFixed(2)}
                  </span>
                  <span className="text-gray-300 relative z-10">
                    {bid.quantity.toFixed(6)}
                  </span>
                </div>
              ))}
              {(!orderbook?.bids.length) && (
                <p className="text-gray-500 text-center py-4">No bids</p>
              )}
            </div>
          </div>

          <div className="bg-gray-800 rounded-lg p-4">
            <h3 className="text-sm font-medium text-gray-400 mb-3">Order Book - Asks</h3>
            <div className="space-y-1">
              <div className="flex justify-between text-xs text-gray-500 pb-2 border-b border-gray-700">
                <span>Price (USDT)</span>
                <span>Amount (BTC)</span>
              </div>
              {orderbook?.asks.slice(0, 10).map((ask, i) => (
                <div key={i} className="flex justify-between text-sm relative">
                  <div 
                    className="absolute inset-0 bg-red-500/10" 
                    style={{ width: `${Math.min(ask.quantity * 20, 100)}%` }}
                  />
                  <span className="text-red-500 relative z-10">
                    {ask.price.toFixed(2)}
                  </span>
                  <span className="text-gray-300 relative z-10">
                    {ask.quantity.toFixed(6)}
                  </span>
                </div>
              ))}
              {(!orderbook?.asks.length) && (
                <p className="text-gray-500 text-center py-4">No asks</p>
              )}
            </div>
          </div>

          <div className="col-span-2 bg-gray-800 rounded-lg p-4">
            <h3 className="text-sm font-medium text-gray-400 mb-3">Recent Trades</h3>
            <div className="space-y-1">
              <div className="flex justify-between text-xs text-gray-500 pb-2 border-b border-gray-700">
                <span>Price</span>
                <span>Amount</span>
                <span>Time</span>
              </div>
              {recentTrades.slice(0, 10).map((trade) => (
                <div key={trade.id} className="flex justify-between text-sm">
                  <span className="text-gray-300">{parseFloat(trade.price).toFixed(2)}</span>
                  <span className="text-gray-300">{parseFloat(trade.quantity).toFixed(6)}</span>
                  <span className="text-gray-500">
                    {new Date(trade.time).toLocaleTimeString()}
                  </span>
                </div>
              ))}
              {recentTrades.length === 0 && (
                <p className="text-gray-500 text-center py-4">No recent trades</p>
              )}
            </div>
          </div>
        </div>

        <div className="bg-gray-800 rounded-lg p-6">
          <h3 className="text-lg font-semibold text-white mb-4">Place Order</h3>

          <div className="flex mb-4 bg-gray-700 rounded-lg p-1">
            <button
              className={`flex-1 py-2 rounded-md font-medium flex items-center justify-center gap-1 ${
                side === 'buy' 
                  ? 'bg-green-600 text-white' 
                  : 'text-gray-400 hover:text-white'
              }`}
              onClick={() => setSide('buy')}
            >
              <ArrowUpRight size={16} />
              Buy
            </button>
            <button
              className={`flex-1 py-2 rounded-md font-medium flex items-center justify-center gap-1 ${
                side === 'sell' 
                  ? 'bg-red-600 text-white' 
                  : 'text-gray-400 hover:text-white'
              }`}
              onClick={() => setSide('sell')}
            >
              <ArrowDownRight size={16} />
              Sell
            </button>
          </div>

          <div className="flex mb-4 text-sm">
            <button
              className={`flex-1 py-2 ${
                orderType === 'limit' 
                  ? 'text-white border-b-2 border-blue-500' 
                  : 'text-gray-400'
              }`}
              onClick={() => setOrderType('limit')}
            >
              Limit
            </button>
            <button
              className={`flex-1 py-2 ${
                orderType === 'market' 
                  ? 'text-white border-b-2 border-blue-500' 
                  : 'text-gray-400'
              }`}
              onClick={() => setOrderType('market')}
            >
              Market
            </button>
          </div>

          {message.text && (
            <div className={`mb-4 p-3 rounded text-sm ${
              message.type === 'success' 
                ? 'bg-green-500/10 text-green-400' 
                : 'bg-red-500/10 text-red-400'
            }`}>
              {message.text}
            </div>
          )}

          <form onSubmit={handleSubmit} className="space-y-4">
            {orderType === 'limit' && (
              <div>
                <label className="block text-sm text-gray-400 mb-1">
                  Price (USDT)
                </label>
                <input
                  type="number"
                  step="0.01"
                  value={price}
                  onChange={(e) => setPrice(e.target.value)}
                  className="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:border-blue-500"
                  placeholder="0.00"
                  required={orderType === 'limit'}
                />
              </div>
            )}

            <div>
              <label className="block text-sm text-gray-400 mb-1">
                Amount (BTC)
              </label>
              <input
                type="number"
                step="0.00000001"
                value={quantity}
                onChange={(e) => setQuantity(e.target.value)}
                className="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:border-blue-500"
                placeholder="0.00000000"
                required
              />
            </div>

            <div className="text-sm text-gray-400 space-y-1">
              <div className="flex justify-between">
                <span>Available BTC:</span>
                <span>{btcWallet ? parseFloat(btcWallet.balance_available).toFixed(8) : '0.00000000'}</span>
              </div>
              <div className="flex justify-between">
                <span>Available USDT:</span>
                <span>{usdtWallet ? parseFloat(usdtWallet.balance_available).toFixed(2) : '0.00'}</span>
              </div>
            </div>

            <button
              type="submit"
              disabled={submitting}
              className={`w-full py-3 font-medium rounded-lg disabled:opacity-50 ${
                side === 'buy'
                  ? 'bg-green-600 hover:bg-green-700 text-white'
                  : 'bg-red-600 hover:bg-red-700 text-white'
              }`}
            >
              {submitting ? 'Placing Order...' : `${side === 'buy' ? 'Buy' : 'Sell'} BTC`}
            </button>
          </form>
        </div>
      </div>
    </div>
  );
}
