# NexCart security

NexCart ships layered hardening. No web application can be promised "unhackable" — the layers below make attacks
expensive and noisy. Keep PHP, Laravel and this app updated (see UPGRADE.md).

## Built in
| Layer | What it does |
|---|---|
| Sign-in lockout | 5 wrong passwords per email + IP → 15-minute lock. Every failure is logged. |
| Two-factor (TOTP) | Authenticator-app codes + 10 single-use recovery codes. Owners can **require** it for all staff (Admin → Security). |
| Password policy | 10+ characters with letters and numbers on every password form. |
| Idle sign-out | Staff sessions end after inactivity (default 120 min, configurable). |
| Content-Security-Policy | Nonce-based: only our own scripts run; injected scripts are blocked. Also `frame-ancestors`, `object-src 'none'`, `form-action`. |
| Headers | nosniff, X-Frame-Options, HSTS (on HTTPS), COOP, Referrer/Permissions-Policy, no `X-Powered-By`. |
| Scanner guard | Requests for `.env`, `.git`, `wp-login`, `phpmyadmin` … get 404; 5 strikes → the IP is blocked for an hour. |
| Rate limits | login, register, 2FA, cart, wishlist, search, checkout, tracking. |
| Audit trail | Admin → Activity log: sign-ins, failures, 2FA, password changes, permanent deletions. |
| Server logs | Admin → Server logs (owner only), protected by sign-in + permission. |
| Soft delete | Every module deletes into the Recycle Bin first; restore or erase permanently there. |
| Downloads | Backup and prescription files are served `nosniff`, `no-store`; backups are sandboxed. |
| Web server | `.htaccess` refuses dotfiles; directory listing off; secrets are never flashed back into forms. |

## You should also
- Use HTTPS, set `APP_DEBUG=false`, `APP_ENV=production`.
- Set `TRUSTED_HOSTS=shop.example.com` (comma-separated) so Host-header attacks are rejected, and `TRUSTED_PROXIES` if behind a proxy.
- Turn on **Require two-factor** in Admin → Security, and keep the owner's recovery codes offline.
- Keep the `.env`, `storage/` and `vendor/` folders outside the web root (point the domain at `public/`).
- Back up regularly (Admin → Backups) and keep a copy off the server.

## Added later
| Layer | What it does |
|---|---|
| Google reCAPTCHA v2 | Owner enters keys in Admin → Security. Protects sign-in, sign-up, checkout and order tracking; fails closed if Google is unreachable. |
| Sign in with Google | Customers only. Staff accounts can never be entered through Google. Secrets are stored encrypted. |
| Honeypot + timing | Hidden trap field and signed timestamp on public forms stop simple bots. |
| Account takeover guard | Changing email or password needs the current password. |
| Cache / index control | Account, checkout, order and admin pages are `no-store` and `noindex`. |
| Roles & permissions | Build custom roles; every action (including report types, Recycle Bin restore/erase, activity log) has its own permission. Owner is always full. |
| Activity log | Every create / update / delete / restore on key records with old → new values, filterable, CSV export. |

Not included: password-reset by email (use Admin → Staff to set a new password for staff; customers can contact the shop).
