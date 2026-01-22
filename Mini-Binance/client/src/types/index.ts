export interface User {
  id: number;
  name: string;
  email: string;
  role: 'user' | 'admin';
  kyc_status: 'none' | 'pending' | 'approved' | 'rejected';
  mfa_enabled: boolean;
  is_frozen: boolean;
  wallets?: Wallet[];
}

export interface Asset {
  id: number;
  symbol: string;
  name: string;
  precision: number;
}

export interface Wallet {
  id: number;
  user_id: number;
  asset_id: number;
  balance_available: string;
  balance_locked: string;
  asset: Asset;
}

export interface Order {
  id: number;
  user_id: number;
  market: string;
  side: 'buy' | 'sell';
  type: 'limit' | 'market';
  price: string | null;
  quantity: string;
  quantity_filled: string;
  status: 'open' | 'partial' | 'filled' | 'cancelled';
  created_at: string;
}

export interface Trade {
  id: number;
  market: string;
  price: string;
  quantity: string;
  time: string;
}

export interface OrderbookEntry {
  price: number;
  quantity: number;
  count: number;
}

export interface Orderbook {
  market: string;
  bids: OrderbookEntry[];
  asks: OrderbookEntry[];
}

export interface Transaction {
  id: number;
  user_id: number;
  asset_id: number;
  type: 'deposit' | 'withdraw';
  amount: string;
  status: 'pending' | 'approved' | 'rejected';
  asset: Asset;
  created_at: string;
}
