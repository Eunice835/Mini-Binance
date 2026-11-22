# CryptoExchange - Classroom Cryptocurrency Exchange Simulation

A full-featured cryptocurrency exchange simulation for educational purposes (no real money/crypto).

## Project Overview

This is a simulated cryptocurrency exchange platform built with:
- **Backend**: Laravel 12 + PHP 8.4, PostgreSQL, Redis
- **Frontend**: React + Vite + TailwindCSS + TypeScript
- **Authentication**: Laravel Sanctum with TOTP 2FA support

## Project Structure

```
/api                  # Laravel backend
  /app
    /Http/Controllers/Api  # API controllers
    /Models               # Eloquent models
  /database
    /migrations          # Database schema
    /seeders            # Demo data seeder
  /routes/api.php       # API routes

/client               # React frontend
  /src
    /components        # Reusable components
    /pages            # Page components
    /store            # Zustand state management
    /lib              # API client
    /types            # TypeScript interfaces

start.sh              # Startup script for both services
```

## Features Implemented

### Authentication & Security
- User registration with 12+ character password requirement
- Login with optional TOTP 2FA (Google Authenticator compatible)
- Session-based auth with Sanctum tokens (30min session timeout)
- Password reset with secure tokens (60min expiry)
- Account freeze capability
- Audit logging for all security events
- Rate limiting: login (5/min), OTP (3/min), orders (60/min), withdrawals (5/min)
- Security headers: CSP, X-Frame-Options, X-Content-Type-Options, X-XSS-Protection, Referrer-Policy, HSTS

### KYC System
- Document upload (passport, driver's license, national ID)
- Admin approval/rejection workflow
- Status tracking (none, pending, approved, rejected)

### Wallet System
- Multi-asset wallets (BTC, ETH, USDT)
- Simulated deposits and withdrawals
- Admin approval for transactions
- Available/locked balance tracking

### Trading System
- BTC/USDT market
- Limit and market orders
- Order matching engine with price-time priority
- Order book display with bids/asks
- Recent trades display
- Order cancellation

### Admin Panel
- User management (view, freeze/unfreeze)
- KYC document review and approval
- Transaction approval/rejection
- Credit/debit user balances
- Audit log viewer

## Demo Accounts

- **Admin**: admin@exchange.local / admin123456
- **User**: user@exchange.local / user123456
- **2FA User**: secure@exchange.local / secure123456 (TOTP secret: JBSWY3DPEHPK3PXP)
- **Frozen User**: frozen@exchange.local / frozen123456

Admin and User accounts are pre-seeded with:
- 1 BTC, 10 ETH, 10,000 USDT

## API Endpoints

### Public
- `GET /api/market/orderbook?market=BTC-USDT`
- `GET /api/market/trades?market=BTC-USDT`
- `GET /api/market/ticker?market=BTC-USDT`

### Auth
- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/auth/logout`
- `POST /api/auth/forgot-password`
- `POST /api/auth/reset-password`
- `POST /api/auth/enable-2fa`
- `POST /api/auth/verify-2fa`

### User
- `GET /api/me`
- `GET /api/wallets`
- `POST /api/transactions/deposit`
- `POST /api/transactions/withdraw`
- `POST /api/orders`
- `DELETE /api/orders/{id}`
- `GET /api/orders/open`
- `GET /api/orders/history`

### Admin (requires admin role)
- `GET /api/admin/users`
- `POST /api/admin/users/{id}/freeze`
- `POST /api/admin/kyc/{id}/approve`
- `POST /api/admin/credit`
- `GET /api/admin/audit-logs`

## Running the Application

The application starts automatically with the workflow. To restart manually:

```bash
bash start.sh
```

This starts:
1. Redis server (background)
2. Laravel API server (port 8000)
3. Vite dev server (port 5000)

## Database

PostgreSQL database with tables:
- users, assets, wallets, orders, trades, transactions, kyc_documents, audit_logs

To reset and reseed:
```bash
cd api && php artisan migrate:fresh --seed
```

## Development Notes

- Frontend proxies `/api` requests to Laravel backend
- All hosts allowed for Vite (required for Replit iframe)
- Redis used for caching and queue (queue not fully implemented yet)
