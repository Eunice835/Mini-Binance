import { useEffect, useState } from 'react';
import api from '../lib/api';
import type { Wallet as WalletType, Asset } from '../types';
import { Wallet as WalletIcon, ArrowDownToLine, ArrowUpFromLine } from 'lucide-react';

export default function Wallet() {
  const [wallets, setWallets] = useState<WalletType[]>([]);
  const [assets, setAssets] = useState<Asset[]>([]);
  const [loading, setLoading] = useState(true);
  const [modal, setModal] = useState<{ type: 'deposit' | 'withdraw'; assetId: number } | null>(null);
  const [amount, setAmount] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [message, setMessage] = useState({ type: '', text: '' });

  const fetchWallets = async () => {
    try {
      const res = await api.get('/wallets');
      setWallets(res.data);
      setAssets(res.data.map((w: WalletType) => w.asset));
    } catch (e) {
      console.error('Failed to fetch wallets:', e);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchWallets();
  }, []);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!modal) return;
    
    setSubmitting(true);
    setMessage({ type: '', text: '' });

    try {
      const endpoint = modal.type === 'deposit' 
        ? '/transactions/deposit' 
        : '/transactions/withdraw';
      
      await api.post(endpoint, {
        asset_id: modal.assetId,
        amount: parseFloat(amount),
      });

      setMessage({ 
        type: 'success', 
        text: `${modal.type === 'deposit' ? 'Deposit' : 'Withdrawal'} request submitted!` 
      });
      setAmount('');
      fetchWallets();
      setTimeout(() => setModal(null), 2000);
    } catch (err: any) {
      setMessage({ 
        type: 'error', 
        text: err.response?.data?.message || 'Request failed' 
      });
    } finally {
      setSubmitting(false);
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
        <WalletIcon size={24} className="text-blue-500" />
        <h1 className="text-2xl font-bold text-white">Wallet</h1>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {wallets.map((wallet) => (
          <div key={wallet.id} className="bg-gray-800 rounded-lg p-6">
            <div className="flex items-center justify-between mb-6">
              <div className="flex items-center gap-3">
                <div className="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold">
                  {wallet.asset.symbol[0]}
                </div>
                <div>
                  <h3 className="font-semibold text-white text-lg">{wallet.asset.symbol}</h3>
                  <p className="text-sm text-gray-400">{wallet.asset.name}</p>
                </div>
              </div>
            </div>

            <div className="space-y-3 mb-6">
              <div className="flex justify-between items-center">
                <span className="text-gray-400">Available</span>
                <span className="text-white font-medium text-lg">
                  {parseFloat(wallet.balance_available).toFixed(wallet.asset.precision)}
                </span>
              </div>
              <div className="flex justify-between items-center">
                <span className="text-gray-400">Locked</span>
                <span className="text-yellow-500 font-medium">
                  {parseFloat(wallet.balance_locked).toFixed(wallet.asset.precision)}
                </span>
              </div>
              <div className="border-t border-gray-700 pt-3 flex justify-between items-center">
                <span className="text-gray-300 font-medium">Total</span>
                <span className="text-white font-semibold text-lg">
                  {(parseFloat(wallet.balance_available) + parseFloat(wallet.balance_locked)).toFixed(wallet.asset.precision)}
                </span>
              </div>
            </div>

            <div className="flex gap-2">
              <button
                onClick={() => setModal({ type: 'deposit', assetId: wallet.asset.id })}
                className="flex-1 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg flex items-center justify-center gap-2"
              >
                <ArrowDownToLine size={18} />
                Deposit
              </button>
              <button
                onClick={() => setModal({ type: 'withdraw', assetId: wallet.asset.id })}
                className="flex-1 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg flex items-center justify-center gap-2"
              >
                <ArrowUpFromLine size={18} />
                Withdraw
              </button>
            </div>
          </div>
        ))}
      </div>

      {modal && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
          <div className="bg-gray-800 rounded-lg p-6 w-full max-w-md">
            <h2 className="text-xl font-semibold text-white mb-4 capitalize">
              {modal.type} {assets.find(a => a.id === modal.assetId)?.symbol}
            </h2>

            {message.text && (
              <div className={`mb-4 p-3 rounded text-sm ${
                message.type === 'success' 
                  ? 'bg-green-500/10 text-green-400' 
                  : 'bg-red-500/10 text-red-400'
              }`}>
                {message.text}
              </div>
            )}

            <form onSubmit={handleSubmit}>
              <div className="mb-4">
                <label className="block text-sm text-gray-400 mb-2">Amount</label>
                <input
                  type="number"
                  step="0.00000001"
                  value={amount}
                  onChange={(e) => setAmount(e.target.value)}
                  className="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:border-blue-500"
                  placeholder="0.00000000"
                  required
                />
              </div>

              <p className="text-sm text-gray-400 mb-4">
                {modal.type === 'deposit' 
                  ? 'Note: This is a simulated deposit. Admin approval required.'
                  : 'Note: This is a simulated withdrawal. Admin approval required.'
                }
              </p>

              <div className="flex gap-3">
                <button
                  type="button"
                  onClick={() => setModal(null)}
                  className="flex-1 py-3 bg-gray-700 hover:bg-gray-600 text-white rounded-lg"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={submitting}
                  className={`flex-1 py-3 text-white rounded-lg disabled:opacity-50 ${
                    modal.type === 'deposit' 
                      ? 'bg-green-600 hover:bg-green-700' 
                      : 'bg-red-600 hover:bg-red-700'
                  }`}
                >
                  {submitting ? 'Submitting...' : 'Submit'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
