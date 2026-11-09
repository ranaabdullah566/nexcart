# Browser QA (scripts/qa)

Playwright + axe-core crawl of every storefront and admin page — dark / light × desktop / tablet / phone — plus a few
interactive and permission checks. The results of the last run are in [`docs/QA-REPORT.md`](../../docs/QA-REPORT.md).

It needs a **throw-away** database with sample data. Never point it at a real store (the flow script creates and deletes records).

```bash
# 1. a scratch app: SQLite file, sample data, and a server with several workers
export DB_CONNECTION=sqlite DB_DATABASE=/tmp/nexcart-qa.sqlite APP_URL=http://127.0.0.1:8099 \
       SESSION_DRIVER=file CACHE_STORE=file APP_ENV=local PHP_CLI_SERVER_WORKERS=8
touch $DB_DATABASE && php artisan migrate --force
php artisan tinker --execute "$(cat scripts/qa/seed.php)" | tee /tmp/qa-seed.txt     # prints two order links and a JSON line of ids
php artisan serve --port=8099 &

# 2. the crawlers
cd scripts/qa && npm install && npx playwright install chromium   # skip the last step if Chromium is already installed
tail -n1 /tmp/qa-seed.txt > ids.json
ORDER=$(sed -n 1p /tmp/qa-seed.txt); GUEST=$(sed -n 2p /tmp/qa-seed.txt)
for i in 0 1 2 3 4 5; do node storefront.js "$ORDER" "$GUEST" $i & node admin.js $i & done; wait   # i = theme × viewport
node storefront.js "$ORDER" "$GUEST" 9      # Urdu (RTL) and Roman Urdu spot checks
node admin-flow.js                          # popups, validation, delete confirmation, roles
npm run summary                             # totals and one example per problem type
```

Set `BASE=http://…` to test another address. Reports are written to `scripts/qa/out/`.

Notes

- Run it from a shell **without** your real `.env` database variables. The PHPUnit suite is protected (`phpunit.xml` forces an in-memory database), these scripts are not.
- Too many parallel workers on the built-in PHP server can make it slow; use `PHP_CLI_SERVER_WORKERS=8` as above.
- The scanner guard blocks an IP after a few probe URLs. If the crawl suddenly sees 403 everywhere, run `php artisan cache:clear`.
- The *Server logs* page is a third-party package and is reported as is.
