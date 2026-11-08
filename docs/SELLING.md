# Selling NexCart to several businesses

## The model: one deployment per client

Each client gets **their own** database, storage volume, `.env` and domain, all from the same Docker image:

```bash
docker build -t nexcart:latest .                                   # once per release
scripts/provision-client.sh fresh-burgers https://fresh.pk 24001   # a client folder with fresh secrets
(cd clients/fresh-burgers && docker compose up -d)
php artisan nexcart:license:generate "Fresh Burgers" --domain=fresh.pk ...
```

Why this and not one shared multi-tenant system?

- **Isolation by default.** Orders, customers, prescriptions never share a database. One client’s outage, bug or breach cannot touch another.
- **Per-client everything:** version, backups, e-mail, payment keys, theme, modules, domain.
- **Simple to reason about.** The code is single-store; there is no tenant id to forget in a query.

The cost is operational: N clients means N containers to update. `scripts/provision-client.sh` and `nexcart:update` keep that small.

## The pharmacy profile

NexCart is pharmacy-only. Setup applies one profile that switches the pharmacy modules on
(prescriptions, batches / expiry, delivery slots, riders, pickup, reviews), adds product fields (composition, dosage form, strength, manufacturer), theme colours and a starter home page.
Everything can be changed afterwards under **Store settings → Modules**.

## Making each client’s store different

No code per client. Under **Storefront** the client (or you) can switch header, product-page and footer parts on or off, rename any word in any language
(**Interface text**), build the menus and footer columns, pick colours and logo, and compose the home page from eleven section types.
Hand them [CUSTOMIZING.md](CUSTOMIZING.md). Roles are editable too, so a client can create e.g. a *cashier* role without you.

## Handing over a store

1. Provision and start the deployment, open the site and run the wizard with the client’s details (or let them).
2. Optional: **System health → Add sample products** for a demo, remove it before go-live.
3. Walk them through **docs/ADMIN-GUIDE.md**, send the license key, confirm backups and the scheduler cron are on.

## Multi-tenant SaaS (not built)

A single installation serving many stores with subscriptions is a different product: every table needs tenant isolation,
storage and settings must be per-tenant, plus billing, onboarding and noisy-neighbour limits. It is deliberately **not** part of NexCart 1.x.
If you outgrow “one deployment per client”, the realistic route is database-per-tenant (e.g. the `stancl/tenancy` package) done as its own project with its own test suite.
