# CryptoExchange - Classroom Cryptocurrency Exchange Simulation

A full-featured cryptocurrency exchange simulation for educational purposes (no real money/crypto).

## Tech Stack

- **Backend**: Laravel 12 + PHP 8.4, PostgreSQL, Redis
- **Frontend**: React 19 + Vite 7 + TailwindCSS 4 + TypeScript
- **Authentication**: Laravel Sanctum with TOTP 2FA support

## Features

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

## Requirements

- PHP 8.4 with extensions: pdo_pgsql, redis, bcmath
- Composer (PHP package manager)
- Node.js 20+ and npm
- PostgreSQL 15+
- Redis 7+

---

## Local Deployment (Step-by-Step)

### Prerequisites

Make sure you have the following installed on your system:

1. **PHP 8.4** with extensions: `pdo_pgsql`, `redis`, `bcmath`, `openssl`, `mbstring`
2. **Composer** - https://getcomposer.org/
3. **Node.js 20+** and npm - https://nodejs.org/
4. **PostgreSQL 15+** - https://www.postgresql.org/
5. **Redis 7+** - https://redis.io/

### Step 1: Clone the Repository

```bash
git clone <repository-url>
cd crypto-exchange
```

### Step 2: Create PostgreSQL Database

Open your PostgreSQL client (psql, pgAdmin, or similar) and run:

```sql
CREATE DATABASE minibinance;
CREATE USER minibinance WITH PASSWORD 'secret';
GRANT ALL PRIVILEGES ON DATABASE minibinance TO minibinance;
ALTER DATABASE minibinance OWNER TO minibinance;
```

Or using command line:

```bash
sudo -u postgres psql -c "CREATE DATABASE minibinance;"
sudo -u postgres psql -c "CREATE USER minibinance WITH PASSWORD 'secret';"
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE minibinance TO minibinance;"
sudo -u postgres psql -c "ALTER DATABASE minibinance OWNER TO minibinance;"
```

### Step 3: Setup the Backend (Laravel API)

```bash
cd api
composer install
cp .env.example .env
```

The `.env.example` already has the correct database credentials:

```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=minibinance
DB_USERNAME=minibinance
DB_PASSWORD=secret
```

Generate the application key and run migrations with seed data:

```bash
php artisan key:generate
php artisan migrate --seed
```

This creates all database tables and seeds demo accounts.

### Step 4: Setup the Frontend (React)

```bash
cd ../client
npm install
```

### Step 5: Start All Services

You need to run 3 services. Open **three separate terminal windows**:

**Terminal 1 - Start Redis Server:**
```bash
redis-server
```

**Terminal 2 - Start Laravel API Server:**
```bash
cd api
php artisan serve --host=0.0.0.0 --port=8000
```

**Terminal 3 - Start React Frontend:**
```bash
cd client
npm run dev
```

### Step 6: Access the Application

Open your browser and go to: **http://localhost:5000**

You can now log in with any of the demo accounts listed below.

---

## Docker Deployment (Alternative)

If you prefer Docker, you can start everything with a single command:

### Step 1: Clone the Repository

```bash
git clone <repository-url>
cd crypto-exchange
```

### Step 2: Start with Docker Compose

```bash
docker-compose up -d
```

This automatically starts:
- PostgreSQL database (port 5432)
- Redis server (port 6379)
- Laravel API (via nginx on port 8080)
- React frontend (port 5173)
- MailHog for email testing (port 8025)
- Queue worker

### Step 3: Run Migrations

After containers are running, run migrations:

```bash
docker-compose exec api php artisan migrate --seed
```

### Step 4: Access the Application

- **Frontend**: http://localhost:5173
- **API**: http://localhost:8080/api
- **MailHog (email testing)**: http://localhost:8025

### Docker Commands

```bash
# Start all services
docker-compose up -d

# Stop all services
docker-compose down

# View logs
docker-compose logs -f

# Reset database
docker-compose exec api php artisan migrate:fresh --seed

# Rebuild containers
docker-compose up -d --build
```

---

## Demo Accounts

| Role  | Email                  | Password     | Notes |
|-------|------------------------|--------------|-------|
| Admin | admin@exchange.local   | admin123456  | Full admin access |
| User  | user@exchange.local    | user123456   | Standard user |
| 2FA User | secure@exchange.local | secure123456 | Has 2FA enabled (TOTP secret: JBSWY3DPEHPK3PXP) |
| Frozen | frozen@exchange.local | frozen123456 | Account is frozen |

Admin and User accounts are pre-seeded with: 1 BTC, 10 ETH, 10,000 USDT

---

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

/deploy               # Deployment configs (nginx, etc.)
docker-compose.yml    # Docker orchestration
```

## Database

PostgreSQL database with tables:
- users, assets, wallets, orders, trades, transactions, kyc_documents, audit_logs

To reset and reseed:
```bash
cd api && php artisan migrate:fresh --seed
```

## API Endpoints

### Public
- `GET /api/market/orderbook?market=BTC-USDT`
- `GET /api/market/trades?market=BTC-USDT`
- `GET /api/market/ticker?market=BTC-USDT`

### Authentication
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

## Running Tests

### Backend Tests (PHPUnit)

```bash
cd api
php artisan test
```

Tests cover:
- Authentication (registration, login, logout, 2FA, frozen accounts)
- Orders (placing, cancelling, balance validation)
- Wallets (deposits, withdrawals, balance checks)
- Market endpoints (orderbook, trades, ticker)

### Frontend Tests (Vitest)

```bash
cd client
npm run test
```

Tests cover:
- Layout component rendering
- Navigation and user display
- Auth store state management

## Documentation

- [Security Design](docs/SECURITY.md) - Threat model, authentication, rate limiting, security headers
- [Architecture Overview](docs/ARCHITECTURE.md) - System diagrams, ERD, API structure, data flow

## Troubleshooting

### Database Connection Issues
- Verify PostgreSQL is running: `sudo systemctl status postgresql`
- Check credentials match in `.env` file
- Ensure database exists: `psql -l | grep minibinance`

### Redis Connection Issues
- Verify Redis is running: `redis-cli ping` (should return PONG)
- Start Redis: `redis-server` or `sudo systemctl start redis`

### Frontend Not Loading
- Ensure Vite is running on port 5000
- Check that Laravel API is running on port 8000
- Clear browser cache and hard refresh

### Permission Issues (Linux/Mac)
```bash
cd api
chmod -R 775 storage bootstrap/cache
```

## License

This project is for educational purposes only.
