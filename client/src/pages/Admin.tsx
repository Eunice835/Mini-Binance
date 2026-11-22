import { useEffect, useState } from 'react';
import api from '../lib/api';
import { Users, FileCheck, CreditCard, ScrollText, Check, X } from 'lucide-react';

interface AdminUser {
  id: number;
  name: string;
  email: string;
  role: string;
  kyc_status: string;
  is_frozen: boolean;
  created_at: string;
}

interface KycDoc {
  id: number;
  user_id: number;
  doc_type: string;
  status: string;
  created_at: string;
  user: { name: string; email: string };
}

interface PendingTx {
  id: number;
  user_id: number;
  type: string;
  amount: string;
  status: string;
  created_at: string;
  user: { name: string; email: string };
  asset: { symbol: string };
}

export default function Admin() {
  const [tab, setTab] = useState<'users' | 'kyc' | 'transactions' | 'logs'>('users');
  const [users, setUsers] = useState<AdminUser[]>([]);
  const [kycDocs, setKycDocs] = useState<KycDoc[]>([]);
  const [pendingTx, setPendingTx] = useState<PendingTx[]>([]);
  const [logs, setLogs] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  const fetchData = async () => {
    setLoading(true);
    try {
      if (tab === 'users') {
        const res = await api.get('/admin/users');
        setUsers(res.data.data || res.data);
      } else if (tab === 'kyc') {
        const res = await api.get('/admin/kyc/pending');
        setKycDocs(res.data.data || res.data);
      } else if (tab === 'transactions') {
        const res = await api.get('/admin/transactions/pending');
        setPendingTx(res.data.data || res.data);
      } else if (tab === 'logs') {
        const res = await api.get('/admin/audit-logs');
        setLogs(res.data.data || res.data);
      }
    } catch (e) {
      console.error('Failed to fetch admin data:', e);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchData();
  }, [tab]);

  const freezeUser = async (userId: number) => {
    await api.post(`/admin/users/${userId}/freeze`);
    fetchData();
  };

  const unfreezeUser = async (userId: number) => {
    await api.post(`/admin/users/${userId}/unfreeze`);
    fetchData();
  };

  const approveKyc = async (docId: number) => {
    await api.post(`/admin/kyc/${docId}/approve`);
    fetchData();
  };

  const rejectKyc = async (docId: number) => {
    const notes = prompt('Rejection reason:');
    if (notes) {
      await api.post(`/admin/kyc/${docId}/reject`, { notes });
      fetchData();
    }
  };

  const approveTx = async (txId: number) => {
    await api.post(`/admin/transactions/${txId}/approve`);
    fetchData();
  };

  const rejectTx = async (txId: number) => {
    const notes = prompt('Rejection reason:');
    await api.post(`/admin/transactions/${txId}/reject`, { notes });
    fetchData();
  };

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold text-white">Admin Panel</h1>

      <div className="flex gap-2 bg-gray-800 p-1 rounded-lg">
        {[
          { key: 'users', icon: Users, label: 'Users' },
          { key: 'kyc', icon: FileCheck, label: 'KYC' },
          { key: 'transactions', icon: CreditCard, label: 'Transactions' },
          { key: 'logs', icon: ScrollText, label: 'Audit Logs' },
        ].map(({ key, icon: Icon, label }) => (
          <button
            key={key}
            onClick={() => setTab(key as any)}
            className={`flex-1 py-2 px-4 rounded-md flex items-center justify-center gap-2 ${
              tab === key 
                ? 'bg-blue-600 text-white' 
                : 'text-gray-400 hover:text-white'
            }`}
          >
            <Icon size={18} />
            {label}
          </button>
        ))}
      </div>

      {loading ? (
        <div className="flex items-center justify-center h-64">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
        </div>
      ) : (
        <>
          {tab === 'users' && (
            <div className="bg-gray-800 rounded-lg overflow-hidden">
              <table className="w-full">
                <thead className="bg-gray-700">
                  <tr className="text-gray-400 text-sm">
                    <th className="text-left py-3 px-4">Name</th>
                    <th className="text-left py-3 px-4">Email</th>
                    <th className="text-center py-3 px-4">Role</th>
                    <th className="text-center py-3 px-4">KYC</th>
                    <th className="text-center py-3 px-4">Status</th>
                    <th className="text-center py-3 px-4">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {users.map((user) => (
                    <tr key={user.id} className="border-t border-gray-700">
                      <td className="py-3 px-4 text-white">{user.name}</td>
                      <td className="py-3 px-4 text-gray-400">{user.email}</td>
                      <td className="py-3 px-4 text-center">
                        <span className={`px-2 py-1 rounded text-xs ${
                          user.role === 'admin' 
                            ? 'bg-purple-500/20 text-purple-400' 
                            : 'bg-gray-500/20 text-gray-400'
                        }`}>
                          {user.role}
                        </span>
                      </td>
                      <td className="py-3 px-4 text-center">
                        <span className={`px-2 py-1 rounded text-xs capitalize ${
                          user.kyc_status === 'approved' ? 'bg-green-500/20 text-green-400' :
                          user.kyc_status === 'pending' ? 'bg-yellow-500/20 text-yellow-400' :
                          user.kyc_status === 'rejected' ? 'bg-red-500/20 text-red-400' :
                          'bg-gray-500/20 text-gray-400'
                        }`}>
                          {user.kyc_status}
                        </span>
                      </td>
                      <td className="py-3 px-4 text-center">
                        <span className={`px-2 py-1 rounded text-xs ${
                          user.is_frozen 
                            ? 'bg-red-500/20 text-red-400' 
                            : 'bg-green-500/20 text-green-400'
                        }`}>
                          {user.is_frozen ? 'Frozen' : 'Active'}
                        </span>
                      </td>
                      <td className="py-3 px-4 text-center">
                        {user.is_frozen ? (
                          <button
                            onClick={() => unfreezeUser(user.id)}
                            className="text-green-400 hover:text-green-300 text-sm"
                          >
                            Unfreeze
                          </button>
                        ) : (
                          <button
                            onClick={() => freezeUser(user.id)}
                            className="text-red-400 hover:text-red-300 text-sm"
                          >
                            Freeze
                          </button>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}

          {tab === 'kyc' && (
            <div className="bg-gray-800 rounded-lg overflow-hidden">
              <table className="w-full">
                <thead className="bg-gray-700">
                  <tr className="text-gray-400 text-sm">
                    <th className="text-left py-3 px-4">User</th>
                    <th className="text-left py-3 px-4">Document Type</th>
                    <th className="text-left py-3 px-4">Submitted</th>
                    <th className="text-center py-3 px-4">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {kycDocs.map((doc) => (
                    <tr key={doc.id} className="border-t border-gray-700">
                      <td className="py-3 px-4">
                        <div className="text-white">{doc.user?.name}</div>
                        <div className="text-gray-400 text-sm">{doc.user?.email}</div>
                      </td>
                      <td className="py-3 px-4 text-gray-300 capitalize">
                        {doc.doc_type.replace('_', ' ')}
                      </td>
                      <td className="py-3 px-4 text-gray-400 text-sm">
                        {new Date(doc.created_at).toLocaleString()}
                      </td>
                      <td className="py-3 px-4 text-center">
                        <div className="flex justify-center gap-2">
                          <button
                            onClick={() => approveKyc(doc.id)}
                            className="p-2 bg-green-600 hover:bg-green-700 rounded text-white"
                          >
                            <Check size={16} />
                          </button>
                          <button
                            onClick={() => rejectKyc(doc.id)}
                            className="p-2 bg-red-600 hover:bg-red-700 rounded text-white"
                          >
                            <X size={16} />
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
              {kycDocs.length === 0 && (
                <p className="text-gray-500 text-center py-8">No pending KYC documents</p>
              )}
            </div>
          )}

          {tab === 'transactions' && (
            <div className="bg-gray-800 rounded-lg overflow-hidden">
              <table className="w-full">
                <thead className="bg-gray-700">
                  <tr className="text-gray-400 text-sm">
                    <th className="text-left py-3 px-4">User</th>
                    <th className="text-left py-3 px-4">Type</th>
                    <th className="text-right py-3 px-4">Amount</th>
                    <th className="text-left py-3 px-4">Date</th>
                    <th className="text-center py-3 px-4">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {pendingTx.map((tx) => (
                    <tr key={tx.id} className="border-t border-gray-700">
                      <td className="py-3 px-4">
                        <div className="text-white">{tx.user?.name}</div>
                        <div className="text-gray-400 text-sm">{tx.user?.email}</div>
                      </td>
                      <td className="py-3 px-4">
                        <span className={`capitalize ${
                          tx.type === 'deposit' ? 'text-green-500' : 'text-red-500'
                        }`}>
                          {tx.type}
                        </span>
                      </td>
                      <td className="py-3 px-4 text-right text-white">
                        {parseFloat(tx.amount).toFixed(8)} {tx.asset?.symbol}
                      </td>
                      <td className="py-3 px-4 text-gray-400 text-sm">
                        {new Date(tx.created_at).toLocaleString()}
                      </td>
                      <td className="py-3 px-4 text-center">
                        <div className="flex justify-center gap-2">
                          <button
                            onClick={() => approveTx(tx.id)}
                            className="p-2 bg-green-600 hover:bg-green-700 rounded text-white"
                          >
                            <Check size={16} />
                          </button>
                          <button
                            onClick={() => rejectTx(tx.id)}
                            className="p-2 bg-red-600 hover:bg-red-700 rounded text-white"
                          >
                            <X size={16} />
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
              {pendingTx.length === 0 && (
                <p className="text-gray-500 text-center py-8">No pending transactions</p>
              )}
            </div>
          )}

          {tab === 'logs' && (
            <div className="bg-gray-800 rounded-lg overflow-hidden">
              <table className="w-full">
                <thead className="bg-gray-700">
                  <tr className="text-gray-400 text-sm">
                    <th className="text-left py-3 px-4">Date</th>
                    <th className="text-left py-3 px-4">Actor</th>
                    <th className="text-left py-3 px-4">Action</th>
                    <th className="text-left py-3 px-4">Target</th>
                    <th className="text-left py-3 px-4">IP</th>
                  </tr>
                </thead>
                <tbody>
                  {logs.map((log) => (
                    <tr key={log.id} className="border-t border-gray-700">
                      <td className="py-3 px-4 text-gray-400 text-sm">
                        {new Date(log.created_at).toLocaleString()}
                      </td>
                      <td className="py-3 px-4 text-white">
                        {log.actor?.name || 'System'}
                      </td>
                      <td className="py-3 px-4 text-gray-300">{log.action}</td>
                      <td className="py-3 px-4 text-gray-400">
                        {log.target_type ? `${log.target_type} #${log.target_id}` : '-'}
                      </td>
                      <td className="py-3 px-4 text-gray-500 text-sm">{log.ip_address}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
              {logs.length === 0 && (
                <p className="text-gray-500 text-center py-8">No audit logs</p>
              )}
            </div>
          )}
        </>
      )}
    </div>
  );
}
