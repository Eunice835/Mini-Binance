import { useEffect, useState } from 'react';
import api from '../lib/api';
import type { Order, Transaction } from '../types';
import { ArrowUpRight, ArrowDownRight, History, X } from 'lucide-react';

export default function Portfolio() {
  const [orders, setOrders] = useState<Order[]>([]);
  const [transactions, setTransactions] = useState<Transaction[]>([]);
  const [tab, setTab] = useState<'orders' | 'transactions'>('orders');
  const [loading, setLoading] = useState(true);

  const fetchData = async () => {
    try {
      const [ordersRes, txRes] = await Promise.all([
        api.get('/orders/history'),
        api.get('/transactions'),
      ]);
      setOrders(ordersRes.data.data || ordersRes.data);
      setTransactions(txRes.data.data || txRes.data);
    } catch (e) {
      console.error('Failed to fetch portfolio:', e);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchData();
  }, []);

  const cancelOrder = async (orderId: number) => {
    try {
      await api.delete(`/orders/${orderId}`);
      fetchData();
    } catch (e) {
      console.error('Failed to cancel order:', e);
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-2">
        <History size={24} className="text-blue-500" />
        <h1 className="text-2xl font-bold text-white">Portfolio</h1>
      </div>

      <div className="flex gap-4 border-b border-gray-700">
        <button
          className={`py-3 px-4 font-medium ${
            tab === 'orders' 
              ? 'text-white border-b-2 border-blue-500' 
              : 'text-gray-400 hover:text-white'
          }`}
          onClick={() => setTab('orders')}
        >
          Order History
        </button>
        <button
          className={`py-3 px-4 font-medium ${
            tab === 'transactions' 
              ? 'text-white border-b-2 border-blue-500' 
              : 'text-gray-400 hover:text-white'
          }`}
          onClick={() => setTab('transactions')}
        >
          Transactions
        </button>
      </div>

      {tab === 'orders' && (
        <div className="bg-gray-800 rounded-lg overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-gray-700">
                <tr className="text-gray-400 text-sm">
                  <th className="text-left py-3 px-4">Date</th>
                  <th className="text-left py-3 px-4">Market</th>
                  <th className="text-left py-3 px-4">Side</th>
                  <th className="text-left py-3 px-4">Type</th>
                  <th className="text-right py-3 px-4">Price</th>
                  <th className="text-right py-3 px-4">Qty</th>
                  <th className="text-right py-3 px-4">Filled</th>
                  <th className="text-center py-3 px-4">Status</th>
                  <th className="text-center py-3 px-4">Action</th>
                </tr>
              </thead>
              <tbody>
                {orders.map((order) => (
                  <tr key={order.id} className="border-t border-gray-700">
                    <td className="py-3 px-4 text-gray-400 text-sm">
                      {new Date(order.created_at).toLocaleString()}
                    </td>
                    <td className="py-3 px-4 text-white">{order.market}</td>
                    <td className="py-3 px-4">
                      <span className={`flex items-center gap-1 ${
                        order.side === 'buy' ? 'text-green-500' : 'text-red-500'
                      }`}>
                        {order.side === 'buy' ? <ArrowUpRight size={16} /> : <ArrowDownRight size={16} />}
                        {order.side.toUpperCase()}
                      </span>
                    </td>
                    <td className="py-3 px-4 text-gray-300 capitalize">{order.type}</td>
                    <td className="py-3 px-4 text-right text-white">
                      {order.price ? parseFloat(order.price).toFixed(2) : '-'}
                    </td>
                    <td className="py-3 px-4 text-right text-white">
                      {parseFloat(order.quantity).toFixed(8)}
                    </td>
                    <td className="py-3 px-4 text-right text-gray-400">
                      {parseFloat(order.quantity_filled).toFixed(8)}
                    </td>
                    <td className="py-3 px-4 text-center">
                      <span className={`px-2 py-1 rounded text-xs capitalize ${
                        order.status === 'filled' ? 'bg-green-500/20 text-green-400' :
                        order.status === 'cancelled' ? 'bg-gray-500/20 text-gray-400' :
                        order.status === 'partial' ? 'bg-yellow-500/20 text-yellow-400' :
                        'bg-blue-500/20 text-blue-400'
                      }`}>
                        {order.status}
                      </span>
                    </td>
                    <td className="py-3 px-4 text-center">
                      {['open', 'partial'].includes(order.status) && (
                        <button
                          onClick={() => cancelOrder(order.id)}
                          className="p-1 text-red-400 hover:text-red-300"
                          title="Cancel Order"
                        >
                          <X size={18} />
                        </button>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
            {orders.length === 0 && (
              <p className="text-gray-500 text-center py-8">No orders yet</p>
            )}
          </div>
        </div>
      )}

      {tab === 'transactions' && (
        <div className="bg-gray-800 rounded-lg overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-gray-700">
                <tr className="text-gray-400 text-sm">
                  <th className="text-left py-3 px-4">Date</th>
                  <th className="text-left py-3 px-4">Type</th>
                  <th className="text-left py-3 px-4">Asset</th>
                  <th className="text-right py-3 px-4">Amount</th>
                  <th className="text-center py-3 px-4">Status</th>
                </tr>
              </thead>
              <tbody>
                {transactions.map((tx) => (
                  <tr key={tx.id} className="border-t border-gray-700">
                    <td className="py-3 px-4 text-gray-400 text-sm">
                      {new Date(tx.created_at).toLocaleString()}
                    </td>
                    <td className="py-3 px-4">
                      <span className={`capitalize ${
                        tx.type === 'deposit' ? 'text-green-500' : 'text-red-500'
                      }`}>
                        {tx.type}
                      </span>
                    </td>
                    <td className="py-3 px-4 text-white">{tx.asset?.symbol}</td>
                    <td className="py-3 px-4 text-right text-white">
                      {parseFloat(tx.amount).toFixed(8)}
                    </td>
                    <td className="py-3 px-4 text-center">
                      <span className={`px-2 py-1 rounded text-xs capitalize ${
                        tx.status === 'approved' ? 'bg-green-500/20 text-green-400' :
                        tx.status === 'rejected' ? 'bg-red-500/20 text-red-400' :
                        'bg-yellow-500/20 text-yellow-400'
                      }`}>
                        {tx.status}
                      </span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
            {transactions.length === 0 && (
              <p className="text-gray-500 text-center py-8">No transactions yet</p>
            )}
          </div>
        </div>
      )}
    </div>
  );
}
