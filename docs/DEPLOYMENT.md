# Going live: checklist

Work through this before taking real orders. **System health** in the admin checks most of it for you.

| ✔ | Item | Why |
|---|------|-----|
| ☐ | `APP_ENV=production`, `APP_DEBUG=false` | Debug pages show passwords and keys to anyone who triggers an error. |
| ☐ | HTTPS on the real domain, `APP_URL=https://…` | Card payments, logins and cookies must be encrypted. With an https `APP_URL` the session cookie is marked secure by itself. Behind a proxy set `TRUSTED_PROXIES=*`. |
| ☐ | `TRUSTED_HOSTS=shop.example.com` (comma-separated) | Rejects requests that arrive with a forged Host header. |
| ☐ | Document root is `public/` | Keeps `.env`, `storage/` and source code unreachable from the web. `public/.htaccess` also refuses dotfiles. |
| ☐ | Real e-mail: `MAIL_MAILER=smtp` + credentials | With `log` customers never receive order e-mails. Send yourself a test order. |
| ☐ | Scheduler cron | Cart reminders, nightly backups, Recycle Bin and alert clean-up. System health shows “Scheduler: Running”. |
| ☐ | Automatic backups **and** an off-server copy | Backups land in `storage/app/backups`. Copy them elsewhere (S3, another server, your laptop). |
| ☐ | `php artisan storage:link` | Product images and the logo are served from `public/storage`. |
| ☐ | `storage/` is writable (including `storage/app/ui-overrides`) | Uploads, backups, logs and your own interface wording are saved there. |
| ☐ | Stripe: live secret key, one real test payment | Test keys do not move money; live keys must be tried once. |
| ☐ | Google reCAPTCHA (optional): site + secret key in Admin → Security | Stops bots on sign-in, sign-up, checkout and order tracking. If Google cannot be reached the form is refused (it never waves bots through). |
| ☐ | Google sign-in (optional): client ID + secret, redirect URI `https://your-domain/auth/google/callback` | Customers only — staff always use password + two-factor. |
| ☐ | Shipping zones, tax rate, payment methods, delivery slots | Without a shipping zone customers cannot check out. |
| ☐ | Owner password is strong and two-factor is on; staff have the right role | Riders and pharmacists see far less than managers. Consider “Require two-factor for all staff”. |
| ☐ | Store contact, WhatsApp number and social links filled in | They feed the footer, content pages and product page. |
| ☐ | License key entered (if you bought one) | Removes the license reminder and the footer credit. |

## Things worth knowing

- **Files that matter:** `.env` (secrets, `APP_KEY` — losing the key makes encrypted settings such as the Stripe, reCAPTCHA and Google secrets unreadable) and `storage/app/` (uploads, prescriptions, backups, interface wording).
- **Prescriptions are private.** They are stored outside the web root and only staff with the right permission can open them. Backups contain them, so protect backup files like medical records.
- **Security headers** (no-sniff, frame, referrer, HSTS on HTTPS, cross-origin policy) and a nonce-based **Content-Security-Policy** are sent on every page; account, checkout and admin pages are also `no-store` and `noindex`. The server-log viewer is the one place without the CSP (it ships its own scripts) and is owner-only. If you add your own inline `<script>` to a view, give it `nonce="{{ csp_nonce() }}"`.
- **Scanner protection:** requests for `.env`, `.git`, `wp-login`, `phpmyadmin` … get 404 and repeat offenders are blocked for an hour (kept in the cache, so no database writes).
- **No queue worker is needed.** E-mails are sent while the request runs; a failed e-mail never blocks an order.
- **Performance:** in production `php artisan nexcart:update` caches config, routes and views. OPcache is on in the Docker image.
- **Monitoring:** `GET /up` returns 200 when the app boots — point an uptime monitor at it.
- **Password reset by e-mail is not included.** Reset a staff password in Admin → Staff; a customer can ask the shop (Admin → Customers → Edit sets a new one).
