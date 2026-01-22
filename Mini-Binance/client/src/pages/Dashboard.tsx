import { useEffect, useState } from 'react';
import { useAuthStore } from '../store/authStore';
import api from '../lib/api';
import type { Wallet, Order } from '../types';
import { Wallet as WalletIcon, TrendingUp, Clock, ArrowUpRight, ArrowDownRight } from 'lucide-react';

export default function Dashboard() {
  const user = useAuthStore((state) => state.user);
  const [wallets, setWallets] = useState<Wallet[]>([]);
  const [openOrders, setOpenOrders] = useState<Order[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchData = async () => {
      try {
        const [walletsRes, ordersRes] = await Promise.all([
          api.get('/wallets'),
          api.get('/orders/open'),
        ]);
        setWallets(walletsRes.data);
        setOpenOrders(ordersRes.data);
      } catch (e) {
        console.error('Failed to fetch data:', e);
      } finally {
        setLoading(false);
      }
    };
    fetchData();
  }, []);

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
        <h1 className="text-2xl font-bold text-white">Dashboard</h1>
        <div className="text-sm text-gray-400">
          Welcome, {user?.name}
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        {wallets.map((wallet) => (
          <div key={wallet.id} className="bg-gray-800 rounded-lg p-6">
            <div className="flex items-center justify-between mb-4">
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center">
                  <WalletIcon size={20} className="text-white" />
                </div>
                <div>
                  <h3 className="font-semibold text-white">{wallet.asset.symbol}</h3>
                  <p className="text-sm text-gray-400">{wallet.asset.name}</p>
                </div>
              </div>
            </div>
            <div className="space-y-2">
              <div className="flex justify-between">
                <span className="text-gray-400">Available</span>
                <span className="text-white font-medium">
                  {parseFloat(wallet.balance_available).toFixed(wallet.asset.precision)}
                </span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-400">Locked</span>
                <span className="text-yellow-500 font-medium">
                  {parseFloat(wallet.balance_locked).toFixed(wallet.asset.precision)}
                </span>
              </div>
            </div>
          </div>
        ))}
      </div>

      <div className="bg-gray-800 rounded-lg p-6">
        <div className="flex items-center gap-2 mb-4">
          <Clock size={20} className="text-blue-500" />
          <h2 className="text-xl font-semibold text-white">Open Orders</h2>
        </div>

        {openOrders.length === 0 ? (
          <p className="text-gray-400 text-center py-8">No open orders</p>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead>
                <tr className="text-gray-400 text-sm">
                  <th className="text-left py-3">Market</th>
                  <th className="text-left py-3">Side</th>
                  <th className="text-left py-3">Type</th>
                  <th className="text-right py-3">Price</th>
                  <th className="text-right py-3">Quantity</th>
                  <th className="text-right py-3">Filled</th>
                  <th className="text-right py-3">Status</th>
                </tr>
              </thead>
              <tbody>
                {openOrders.map((order) => (
                  <tr key={order.id} className="border-t border-gray-700">
                    <td className="py-3 text-white">{order.market}</td>
                    <td className="py-3">
                      <span className={`flex items-center gap-1 ${
                        order.side === 'buy' ? 'text-green-500' : 'text-red-500'
                      }`}>
                        {order.side === 'buy' ? (
                          <ArrowUpRight size={16} />
                        ) : (
                          <ArrowDownRight size={16} />
                        )}
                        {order.side.toUpperCase()}
                      </span>
                    </td>
                    <td className="py-3 text-gray-300">{order.type}</td>
                    <td className="py-3 text-right text-white">
                      {order.price ? parseFloat(order.price).toFixed(2) : 'Market'}
                    </td>
                    <td className="py-3 text-right text-white">
                      {parseFloat(order.quantity).toFixed(8)}
                    </td>
                    <td className="py-3 text-right text-gray-400">
                      {parseFloat(order.quantity_filled).toFixed(8)}
                    </td>
                    <td className="py-3 text-right">
                      <span className={`px-2 py-1 rounded text-xs ${
                        order.status === 'open' ? 'bg-blue-500/20 text-blue-400' :
                        order.status === 'partial' ? 'bg-yellow-500/20 text-yellow-400' :
                        'bg-gray-500/20 text-gray-400'
                      }`}>
                        {order.status}
                      </span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {user?.kyc_status !== 'approved' && (
        <div className="bg-yellow-500/10 border border-yellow-500/50 rounded-lg p-4">
          <div className="flex items-center gap-2">
            <TrendingUp size={20} className="text-yellow-500" />
            <span className="text-yellow-500 font-medium">
              Complete KYC verification to unlock full trading features
            </span>
          </div>
          <p className="text-gray-400 text-sm mt-2">
            Current status: <span className="capitalize">{user?.kyc_status}</span>
          </p>
        </div>
      )}
    </div>
  );
}
