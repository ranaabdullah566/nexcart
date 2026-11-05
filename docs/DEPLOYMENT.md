# Going live: checklist

Work through this before taking real orders. **System health** in the admin checks most of it for you.

| ✔ | Item | Why |
|---|------|-----|
| ☐ | `APP_ENV=production`, `APP_DEBUG=false` | Debug pages show passwords and keys to anyone who triggers an error. |
| ☐ | HTTPS on the real domain, `APP_URL=https://…` | Card payments, logins and cookies must be encrypted. Behind a proxy set `TRUSTED_PROXIES=*`. |
| ☐ | Document root is `public/` | Keeps `.env`, `storage/` and source code unreachable from the web. |
| ☐ | Real e-mail: `MAIL_MAILER=smtp` + credentials | With `log` customers never receive order e-mails. Send yourself a test order. |
| ☐ | Scheduler cron | Cart reminders, nightly backups. System health shows “Scheduler: Running”. |
| ☐ | Automatic backups **and** an off-server copy | Backups land in `storage/app/backups`. Copy them elsewhere (S3, another server, your laptop). |
| ☐ | `php artisan storage:link` | Product images and the logo are served from `public/storage`. |
| ☐ | Stripe: live secret key, one real test payment | Test keys do not move money; live keys must be tried once. |
| ☐ | Shipping zones, tax rate, payment methods, delivery slots | Without a shipping zone customers cannot check out. |
| ☐ | Owner password is strong; staff accounts have the right role | Riders and pharmacists see far less than managers. |
| ☐ | License key entered (if you bought one) | Removes the license reminder and the footer credit. |

## Things worth knowing

- **Files that matter:** `.env` (secrets, `APP_KEY` — losing the key makes encrypted settings such as the Stripe key unreadable) and `storage/app/` (uploads, prescriptions, backups).
- **Prescriptions are private.** They are stored outside the web root and only staff with the right permission can open them. Backups contain them, so protect backup files like medical records.
- **Security headers** (no-sniff, frame, referrer, HSTS on HTTPS) are sent on every page. A Content-Security-Policy is not set because the NEXUS UI uses inline scripts.
- **No queue worker is needed.** E-mails are sent while the request runs; a failed e-mail never blocks an order.
- **Performance:** in production `php artisan nexcart:update` caches config, routes and views. OPcache is on in the Docker image.
- **Monitoring:** `GET /up` returns 200 when the app boots — point an uptime monitor at it.
