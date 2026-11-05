# Licensing (for the person selling NexCart)

Licenses are **signed offline keys**. Nothing phones home, so a client’s store keeps working even if your server is gone.

## One-time setup

```bash
php artisan nexcart:license:keys --out=/safe/place
```

- **Public key** → put it in `config/license.php` (or `NEXCART_LICENSE_PUBLIC_KEY`) *before you build a release*. It ships with every copy.
- **Private key** → keep it on your own machine only, backed up. Never deploy it. Lose it and you cannot issue keys; leak it and anyone can.

While the public key is empty, licensing is switched off completely (no reminders, no footer credit) — useful for your own stores.

## Issuing a key

```bash
php artisan nexcart:license:generate "Fresh Burgers Ltd" --email=owner@fresh.pk \
    --domain=fresh.pk --edition=standard --expires=2027-12-31 --private-key=/safe/place/license-private.key
```

`--domain` can be `fresh.pk`, `*.fresh.pk` or `*` (any). Leave `--expires` out for a lifetime key. Send the printed `NXC.…` text to the client; they paste it
in **Admin → License**. `localhost`, `*.test` and `*.localhost` always pass, so staging copies are never blocked.

## What “unlicensed” means

Soft enforcement, by design: an unlicensed, invalid, wrong-domain or expired copy **keeps selling**. It shows
a reminder in the admin and “Powered by NexCart” in the storefront footer. Expired licenses get a 14-day grace period
(reminder only). If you want hard limits later (staff count, features per edition) the edition is already in the key.

## Honest limits

Anyone with the source can remove the checks — as with any self-hosted PHP product. A license key is a business agreement made convenient, not DRM.
