import { create } from 'zustand';
import api from '../lib/api';
import type { User } from '../types';

interface AuthState {
  user: User | null;
  token: string | null;
  isLoading: boolean;
  login: (email: string, password: string, otp?: string) => Promise<{ requires_2fa?: boolean }>;
  register: (name: string, email: string, password: string, password_confirmation: string) => Promise<void>;
  logout: () => Promise<void>;
  fetchUser: () => Promise<void>;
  setToken: (token: string) => void;
}

export const useAuthStore = create<AuthState>((set, get) => ({
  user: null,
  token: localStorage.getItem('token'),
  isLoading: false,

  login: async (email, password, otp) => {
    const response = await api.post('/auth/login', { email, password, otp });
    
    if (response.data.requires_2fa) {
      return { requires_2fa: true };
    }

    const { token, user } = response.data;
    localStorage.setItem('token', token);
    set({ token, user });
    return {};
  },

  register: async (name, email, password, password_confirmation) => {
    const response = await api.post('/auth/register', { 
      name, email, password, password_confirmation 
    });
    const { token, user } = response.data;
    localStorage.setItem('token', token);
    set({ token, user });
  },

  logout: async () => {
    try {
      await api.post('/auth/logout');
    } catch (e) {}
    localStorage.removeItem('token');
    set({ token: null, user: null });
  },

  fetchUser: async () => {
    if (!get().token) return;
    set({ isLoading: true });
    try {
      const response = await api.get('/me');
      set({ user: response.data, isLoading: false });
    } catch (e) {
      localStorage.removeItem('token');
      set({ token: null, user: null, isLoading: false });
    }
  },

  setToken: (token) => {
    localStorage.setItem('token', token);
    set({ token });
  },
}));
