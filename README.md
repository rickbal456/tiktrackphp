# TikTrack (PHP)

Native PHP 8.3 rewrite of the TikTok Conversion Manager: dashboard, TikTok pixel configs, conversions (CSV + send to Events API 2.0), landing pages with cascading redirects, custom domains (DNS verify), profile, roles.

## Requirements

- PHP 8.2+ with extensions: `pdo_mysql`, `json`, `curl`, `mbstring`
- MySQL 8.0 / MariaDB 10.6+
- Apache `mod_rewrite` with `DocumentRoot` pointing to `public/`
- Composer (for PSR-4 autoload only)

## Quick start (Docker)

```bash
cd tiktrack-php
cp .env.example .env
# Edit .env: DB_HOST=mysql DB_PORT=3306 APP_URL=http://localhost:8080
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php database/migrate.php
docker compose exec app php database/seed.php
```

Open **http://localhost:8080** — default admin: `admin@tiktrack.local` / `admin123`.

Inside the app container, MySQL host is `mysql` and port `3306`. On the host machine, MySQL is exposed at `127.0.0.1:3308`.

## Quick start (XAMPP)

1. Clone/copy project to `htdocs/tiktrack-php`.
2. `cp .env.example .env` — set `DB_HOST=127.0.0.1`, `DB_PORT=3306` (or your MySQL port), `DB_NAME=tiktrack_php`, `APP_URL=http://localhost/tiktrack-php/public` (adjust to your vhost).
3. `composer install`
4. `php database/migrate.php` then `php database/seed.php`
5. Point Apache `DocumentRoot` at `tiktrack-php/public` or browse `http://localhost/tiktrack-php/public/`.

## Environment

See [.env.example](.env.example). Important:

| Variable | Purpose |
|----------|---------|
| `APP_URL` | Base URL for redirects and LP URL builder |
| `SAAS_BASE_DOMAINS` | Comma-separated hosts treated as SaaS (not custom domains) |
| `DOMAIN_VERIFY_VPS_IP` | Expected A record IP for domain verification |
| `DOMAIN_VERIFY_LOCAL_MODE` | `true` skips A-record check (local/LAN only) |
| `NEXT_PUBLIC_DOMAIN_VERIFY_LOCAL_MODE` | Shown in UI (Tailwind) |
| `NEXT_PUBLIC_VPS_IP` | Shown to users in domain instructions |
| `NEXT_PUBLIC_CUSTOM_DOMAIN_SCHEME` | `http` or `https` for copied LP URLs (optional) |

## Tailwind

This project uses the **Tailwind Play CDN** in layouts (no Node build).

## TikTok Events API

Endpoint: `POST https://business-api.tiktok.com/open_api/v1.3/event/track/`

## License

Private / PT Goami Bagjatama Indonesia.
