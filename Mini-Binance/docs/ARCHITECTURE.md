# Architecture Overview

## System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                         Client (React)                          │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐           │
│  │  Pages   │ │Components│ │  Stores  │ │   Lib    │           │
│  └──────────┘ └──────────┘ └──────────┘ └──────────┘           │
└─────────────────────────────────────────────────────────────────┘
                              │
                              │ HTTP/JSON
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                     Nginx (Reverse Proxy)                       │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Laravel API (PHP-FPM)                        │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐           │
│  │Controllers│ │ Models  │ │Middleware│ │ Services │           │
│  └──────────┘ └──────────┘ └──────────┘ └──────────┘           │
└─────────────────────────────────────────────────────────────────┘
                              │
              ┌───────────────┼───────────────┐
              ▼               ▼               ▼
┌──────────────────┐ ┌──────────────┐ ┌──────────────┐
│   PostgreSQL     │ │    Redis     │ │   MailHog    │
│   (Database)     │ │   (Cache)    │ │   (Email)    │
└──────────────────┘ └──────────────┘ └──────────────┘
```

## Technology Stack

### Frontend
| Component | Technology |
|-----------|------------|
| Framework | React 19 |
| Build Tool | Vite 7 |
| State Management | Zustand |
| Data Fetching | TanStack Query |
| Styling | Tailwind CSS 4 |
| Forms | React Hook Form + Zod |
| HTTP Client | Axios |
| Routing | React Router 7 |
| Icons | Lucide React |

### Backend
| Component | Technology |
|-----------|------------|
| Framework | Laravel 12 |
| Language | PHP 8.4 |
| Database | PostgreSQL 15 |
| Cache | Redis 7 |
| Authentication | Laravel Sanctum |
| 2FA | TOTP (RFC 6238) |

### Infrastructure
| Component | Technology |
|-----------|------------|
| Container | Docker |
| Web Server | Nginx |
| Process Manager | PHP-FPM |
| Queue Worker | Laravel Queue |
| Email (Dev) | MailHog |

## Entity Relationship Diagram

```
┌──────────────────┐
│      users       │
├──────────────────┤
│ id               │───────────────────────────────────────┐
│ name             │                                       │
│ email            │                                       │
│ password         │                                       │
│ role             │                                       │
│ kyc_status       │                                       │
│ is_frozen        │                                       │
│ totp_secret      │                                       │
│ totp_enabled     │                                       │
│ created_at       │                                       │
│ updated_at       │                                       │
└──────────────────┘                                       │
         │                                                 │
         │ 1:N                                             │
         ▼                                                 │
┌──────────────────┐      ┌──────────────────┐            │
│     wallets      │      │      assets      │            │
├──────────────────┤      ├──────────────────┤            │
│ id               │      │ id               │◄───────────┤
│ user_id          │──────│ symbol           │            │
│ asset_id         │◄─────│ name             │            │
│ balance_available│      │ precision        │            │
│ balance_locked   │      └──────────────────┘            │
│ created_at       │                                      │
│ updated_at       │                                      │
└──────────────────┘                                      │
                                                          │
┌──────────────────┐      ┌──────────────────┐            │
│      orders      │      │      trades      │            │
├──────────────────┤      ├──────────────────┤            │
│ id               │◄────▶│ id               │            │
│ user_id          │──────│ market           │            │
│ market           │      │ buy_order_id     │────────────┤
│ side             │      │ sell_order_id    │            │
│ type             │      │ taker_user_id    │────────────┤
│ price            │      │ maker_user_id    │────────────┤
│ quantity         │      │ price            │            │
│ quantity_filled  │      │ quantity         │            │
│ status           │      │ created_at       │            │
│ created_at       │      └──────────────────┘            │
│ updated_at       │                                      │
└──────────────────┘                                      │
                                                          │
┌──────────────────┐      ┌──────────────────┐            │
│   transactions   │      │  kyc_documents   │            │
├──────────────────┤      ├──────────────────┤            │
│ id               │      │ id               │            │
│ user_id          │──────│ user_id          │────────────┤
│ asset_id         │      │ type             │            │
│ type             │      │ file_path        │            │
│ amount           │      │ status           │            │
│ status           │      │ admin_notes      │            │
│ created_at       │      │ created_at       │            │
│ updated_at       │      │ updated_at       │            │
└──────────────────┘      └──────────────────┘            │
                                                          │
┌──────────────────┐                                      │
│   audit_logs     │                                      │
├──────────────────┤                                      │
│ id               │                                      │
│ user_id          │──────────────────────────────────────┘
│ action           │
│ ip_address       │
│ user_agent       │
│ metadata         │
│ created_at       │
└──────────────────┘
```

## API Structure

### Authentication Endpoints
```
POST /api/auth/register      # User registration
POST /api/auth/login         # User login
POST /api/auth/logout        # User logout
POST /api/auth/forgot-password  # Request password reset
POST /api/auth/reset-password   # Reset password with token
POST /api/auth/enable-2fa    # Enable 2FA
POST /api/auth/verify-2fa    # Verify 2FA setup
POST /api/auth/disable-2fa   # Disable 2FA
```

### User Endpoints
```
GET  /api/me                 # Get current user profile
PUT  /api/me                 # Update profile
GET  /api/wallets            # Get user wallets
POST /api/transactions/deposit   # Request deposit
POST /api/transactions/withdraw  # Request withdrawal
GET  /api/transactions       # Transaction history
```

### Trading Endpoints
```
GET  /api/market/orderbook   # Get order book
GET  /api/market/trades      # Get recent trades
GET  /api/market/ticker      # Get market ticker
POST /api/orders             # Place order
DELETE /api/orders/:id       # Cancel order
GET  /api/orders/open        # Open orders
GET  /api/orders/history     # Order history
GET  /api/trades             # User trade history
```

### KYC Endpoints
```
GET  /api/kyc/status         # Get KYC status
POST /api/kyc/submit         # Submit KYC document
```

### Admin Endpoints
```
GET  /api/admin/users        # List all users
POST /api/admin/users/:id/freeze    # Freeze user
POST /api/admin/users/:id/unfreeze  # Unfreeze user
GET  /api/admin/kyc/pending  # Pending KYC documents
POST /api/admin/kyc/:id/approve     # Approve KYC
POST /api/admin/kyc/:id/reject      # Reject KYC
POST /api/admin/credit       # Credit user balance
POST /api/admin/debit        # Debit user balance
GET  /api/admin/transactions/pending  # Pending transactions
POST /api/admin/transactions/:id/approve  # Approve transaction
POST /api/admin/transactions/:id/reject   # Reject transaction
GET  /api/admin/audit-logs   # View audit logs
```

## Order Matching Engine

The matching engine uses price-time priority:

1. **Buy Orders** - Sorted by price (highest first), then time (oldest first)
2. **Sell Orders** - Sorted by price (lowest first), then time (oldest first)

### Matching Algorithm
```
1. New order received
2. If market order:
   - Match against best available price on opposite side
   - Continue until order filled or no more counterparties
3. If limit order:
   - Match against orders at or better than limit price
   - If partially filled, rest goes to order book
   - If no matches, entire order goes to order book
4. Create trade records for each match
5. Update wallet balances
6. Log audit trail
```

## Data Flow

### Order Placement
```
Client → API → Validate → Lock Funds → Match Engine → Trade/Book → Response
                                              │
                                              ▼
                                    Update Balances
                                              │
                                              ▼
                                      Audit Log
```

### Authentication Flow
```
Login Request → Validate Credentials → Check 2FA → Issue Token → Response
                        │                   │
                        ▼                   ▼
                  Check Frozen     If enabled, require OTP
                        │                   │
                        ▼                   ▼
                  Log Attempt        Verify TOTP
```

## Deployment

### Docker Compose Services
- **api** - Laravel PHP-FPM container
- **web** - Nginx reverse proxy
- **client** - Node.js dev server (development only)
- **db** - PostgreSQL database
- **redis** - Cache and queue
- **worker** - Laravel queue worker
- **mailhog** - Email testing (development only)

### Ports
| Service | Port |
|---------|------|
| Web (Nginx) | 8080 |
| Client Dev | 5173 |
| PostgreSQL | 5432 |
| Redis | 6379 |
| MailHog SMTP | 1025 |
| MailHog Web | 8025 |

## Future Considerations

### Scalability
- Horizontal scaling of API servers
- Read replicas for PostgreSQL
- Redis Cluster for high availability
- Message queue for async operations

### Performance
- Response caching for public endpoints
- WebSocket for real-time order book updates
- Database query optimization
- CDN for static assets

### Features
- Additional trading pairs
- Advanced order types (stop-loss, OCO)
- Trading fee structure
- API key management for programmatic trading
