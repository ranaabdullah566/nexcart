# Installing NexCart

Pick the route that matches your hosting. All three end at the same place: the **store wizard** at `/setup`
where you choose the business type, create the owner account and (optionally) add sample products.

## 1. Web installer — shared hosting or any PHP server (no terminal needed)

Requirements: PHP 8.3+, MySQL/MariaDB (or SQLite for a small store), and these PHP extensions:
`mbstring openssl pdo fileinfo tokenizer xml ctype json curl gd zip` (`sodium` is optional, for license keys).

1. Upload the release zip and extract it. **Point the domain's document root at the `public/` folder.**
2. Make sure `storage/` and `bootstrap/cache/` (and `.env`, if it already exists) are writable by the web server.
3. Open your site. The first visit creates `.env` and an application key for you and shows **Install**:
   it checks the server, asks for your database details, creates the tables and then sends you to the wizard.
4. Finish the wizard at `/setup`. The installer locks itself afterwards (`storage/app/installed.lock`).
5. Open **System health** in the admin and fix anything red or orange (HTTPS, e-mail, the scheduler cron).

If the database user cannot create databases, create an empty one in your hosting panel first.

## 2. Docker — a VPS (recommended for selling to several clients)

```bash
cp .env.docker.example .env
docker compose run --rm app php artisan key:generate --show      # paste the result into APP_KEY
# edit .env: APP_URL, DB_PASSWORD, DB_ROOT_PASSWORD, MAIL_*
docker compose up -d --build
```

The container waits for MySQL, runs the migrations and starts Apache on `APP_PORT` (default 8080). Put a reverse
proxy with HTTPS in front (Caddy, Traefik or nginx + Let’s Encrypt) and open the site to run the wizard.
Uploads, backups and logs live in the `storage` volume, the database in `dbdata` — back both up.
A second container, `scheduler`, runs reminders, nightly backups and the heartbeat.

## 3. Command line — developers

```bash
composer install
cp .env.example .env && php artisan key:generate
# edit DB_* in .env (MySQL) and APP_URL
php artisan migrate
php artisan storage:link
php artisan serve                 # open /setup
```

## After installing

- Cron for the scheduler: `* * * * * cd /path/to/nexcart && php artisan schedule:run >> /dev/null 2>&1`
- Staff accounts: **Admin → Staff**. Roles: owner, manager, staff, rider, pharmacist.
- Try everything with **System health → Add sample products** (removable in one click).
