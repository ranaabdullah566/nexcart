# NexCart security

NexCart ships layered hardening. No web application can be promised "unhackable" — the layers below make attacks
expensive and noisy. Keep PHP, Laravel and this app updated (see [UPGRADE.md](UPGRADE.md)).

## Built in
| Layer | What it does |
|---|---|
| Sign-in lockout | 5 wrong passwords per email + IP → 15-minute lock. Every failure is logged. Sign-in, sign-up and two-factor are also rate-limited. |
| Two-factor (TOTP) | Authenticator-app codes + 10 single-use recovery codes. Owners can **require** it for all staff (Admin → Security). |
| Password policy | 10+ characters with letters and numbers on every password form. |
| Account-takeover guard | Changing an email or password needs the current password; Google sign-in can never open a staff account. |
| Forgot password | “Forgot password?” on the sign-in page e-mails a one-time link (valid 60 minutes, at most 3 links per address per hour). The page says the same thing whether or not the address exists, deleted accounts cannot reset, a reset signs the account out everywhere and clears any sign-in lock, and a staff member with two-factor still needs their code afterwards. |
| Idle sign-out | Staff sessions end after inactivity (default 120 min, configurable). |
| Content-Security-Policy | Nonce-based: only our own scripts run; injected scripts are blocked. Also `frame-ancestors`, `object-src 'none'`, `form-action`. |
| Headers | nosniff, X-Frame-Options, HSTS (on HTTPS), cross-origin opener policy, referrer / permissions policy, no `X-Powered-By`. Account, checkout, order and admin pages are `no-store` and `noindex`. |
| Scanner guard | Requests for `.env`, `.git`, `wp-login`, `phpmyadmin` … get 404; 5 strikes → the IP is blocked for an hour. |
| Rate limits | login, register, 2FA, cart, search, checkout, tracking, contact form, newsletter sign-up, prescription upload. |
| Bot protection | Optional Google reCAPTCHA v2 on sign-in, sign-up, checkout and order tracking (refuses when Google is unreachable), plus an invisible honeypot and a timing check on public forms. |
| Form validation | Every form is checked in the browser *and* again on the server; messages appear under each field. The server never trusts the browser. |
| Sign in with Google | Customers only. Keys are stored encrypted. |
| Roles & permissions | Build custom roles; every action — including each report, Recycle Bin restore / erase, the activity log — has its own permission. The owner is always full and cannot be edited. Updates never undo your role changes. |
| Audit trail | Admin → Activity log: every create / update / delete / restore with old → new values, sign-ins, failures, 2FA and role changes. CSV export. |
| Server logs | Admin → Server logs (owner only), protected by sign-in + permission. |
| Soft delete | Every module deletes into the Recycle Bin first; restore or erase permanently there. |
| Downloads | Backup and prescription files are served `nosniff` and `no-store`; backups are sandboxed. |
| Web server | `.htaccess` refuses dotfiles; directory listing off; secrets are never flashed back into forms. |
| Guest forms | Contact, newsletter and prescription upload need no account but are rate-limited, use the honeypot + timing check, and (if switched on) reCAPTCHA. The newsletter gives the same answer whether or not an address is already on the list. |
| Guest orders | After a guest order, an account only receives the orders placed **from that browser session** — never “all orders with this e-mail”, which would let someone read another person's history. |
| Prescription uploads | Photos / PDFs (JPG, PNG, WebP, PDF, 5 MB each, up to 5) go to a **private disk**, never a public URL; only staff with the prescription permission can open them. |
| Recently viewed | Stored only in the visitor's own browser (`localStorage`), never sent to the server; there is a Clear button. |
| Error pages | 403 / 404 / 419 / 429 / 500 / 503 are branded and work without the database, and never print stack traces in production. |
| Page text | Text typed into pages is escaped first; only headings, lists and links are produced, and links open safely. |

## Setting up the optional parts

**reCAPTCHA** — create a *reCAPTCHA v2 “I’m not a robot”* key pair at google.com/recaptcha, then Admin → Security → *Bot protection & Google sign-in*:
paste the site key and the secret, tick the switch.

**Sign in with Google** — in Google Cloud create an OAuth client (type *Web application*), add the redirect URI shown on the same Security page
(`https://your-domain/auth/google/callback`), paste the client ID and secret, tick the switch. Secrets are saved encrypted and never shown again.

## You should also
- Use HTTPS, set `APP_DEBUG=false`, `APP_ENV=production`.
- Set `TRUSTED_HOSTS=shop.example.com` (comma-separated) so Host-header attacks are rejected, and `TRUSTED_PROXIES` if behind a proxy.
- Turn on **Require two-factor** in Admin → Security, and keep the owner's recovery codes offline.
- Keep the `.env`, `storage/` and `vendor/` folders outside the web root (point the domain at `public/`).
- Back up regularly (Admin → Backups) and keep a copy off the server.
- If you add inline scripts to a view, give them `nonce="{{ csp_nonce() }}"` or the Content-Security-Policy will block them.

Password reset needs working e-mail (`MAIL_MAILER=smtp`). If e-mail is not set up yet, reset a staff password in Admin → Staff and a customer’s in Admin → Customers → Edit.
