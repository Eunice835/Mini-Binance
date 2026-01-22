# Security Design Document

## Overview

This document describes the security architecture and controls implemented in the CryptoExchange simulation platform.

## Threat Model

### Assets
1. **User Credentials** - Passwords, 2FA secrets, session tokens
2. **User Data** - Personal information, KYC documents
3. **Financial Data** - Wallet balances, transaction history, order data
4. **System Integrity** - API endpoints, database, admin functionality

### Threat Actors
1. **External Attackers** - Attempting unauthorized access via internet
2. **Malicious Users** - Registered users attempting privilege escalation
3. **Insider Threats** - Administrators abusing access

### Attack Vectors
| Vector | Mitigation |
|--------|------------|
| Credential Stuffing | Rate limiting (5 attempts/min) |
| Brute Force | Account lockout, strong password policy |
| Session Hijacking | Secure cookies, 30-min timeout |
| SQL Injection | Eloquent ORM, parameterized queries |
| XSS | Content Security Policy headers |
| CSRF | Laravel CSRF tokens, SameSite cookies |
| Privilege Escalation | Role-based access control, middleware |

## Authentication

### Password Requirements
- Minimum 12 characters
- Stored using bcrypt hashing (12 rounds)
- Password reset tokens expire after 60 minutes

### Two-Factor Authentication (2FA)
- TOTP-based (RFC 6238)
- Compatible with Google Authenticator, Authy
- 30-second token validity window
- Rate limited: 3 attempts per minute

### Session Management
- Laravel Sanctum token-based authentication
- 30-minute inactivity timeout
- Tokens stored securely in database
- Single active session per user (optional)

## Authorization

### Role-Based Access Control
| Role | Permissions |
|------|-------------|
| User | View own data, trade, deposit/withdraw |
| Admin | All user permissions + user management, KYC approval, transaction approval |

### Account States
- **Active** - Normal account operation
- **Frozen** - Cannot trade, withdraw, or place orders (admin action)
- **KYC Pending** - Limited withdrawal capability

## Rate Limiting

| Endpoint Category | Limit |
|-------------------|-------|
| Login/Register | 5 requests/minute |
| OTP Verification | 3 requests/minute |
| Trading API | 60 requests/minute |
| Withdrawals | 5 requests/minute |
| General API | 1000 requests/minute |

## Security Headers

All responses include:
```
Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'
X-Frame-Options: DENY
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
Strict-Transport-Security: max-age=31536000; includeSubDomains
```

## Data Protection

### Encryption
- All traffic over HTTPS (TLS 1.2+)
- Database connections encrypted
- Sensitive fields encrypted at rest (2FA secrets)

### KYC Document Security
- Files stored outside web root
- Validated file types (PDF, JPEG, PNG only)
- Maximum file size: 5MB
- Access restricted to document owner and admins

### Database Security
- Parameterized queries via Eloquent ORM
- Minimal privilege database user
- Connection pooling with encrypted connections

## Audit Logging

All security-relevant events are logged:
- Login attempts (success/failure)
- Password changes
- 2FA enable/disable
- Account freeze/unfreeze
- KYC status changes
- Admin actions
- Failed authorization attempts

Log format:
```
{
  "user_id": 123,
  "action": "login_success",
  "ip_address": "192.168.1.1",
  "user_agent": "Mozilla/5.0...",
  "metadata": {...},
  "created_at": "2024-01-15T10:30:00Z"
}
```

## Input Validation

### API Validation
- Laravel Form Requests for all endpoints
- Zod schema validation on frontend
- Type checking with TypeScript

### File Uploads
- MIME type validation
- Extension whitelist
- Virus scanning (recommended for production)
- Filename sanitization

## Incident Response

### Detection
- Automated alerts for:
  - Multiple failed login attempts
  - Unusual withdrawal patterns
  - Rate limit violations
  - Admin action spikes

### Response Procedures
1. Identify and contain the threat
2. Freeze affected accounts if necessary
3. Review audit logs
4. Notify affected users
5. Document and improve controls

## Compliance Considerations

This is a **simulation platform** and does not handle real money or cryptocurrency. For production deployments handling real assets, additional measures would be required:
- KYC/AML compliance
- PCI DSS (if handling payments)
- SOC 2 certification
- Regular penetration testing
- Bug bounty program

## Security Checklist

- [x] HTTPS enforced
- [x] Strong password policy
- [x] 2FA support
- [x] Rate limiting
- [x] Security headers
- [x] CSRF protection
- [x] SQL injection prevention
- [x] XSS prevention
- [x] Session timeout
- [x] Audit logging
- [x] Role-based access control
- [x] Input validation
- [x] Secure file uploads
