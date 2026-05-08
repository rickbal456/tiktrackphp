# TikTrack PHP

TikTrack PHP adalah aplikasi tracking konversi TikTok berbasis native PHP (tanpa framework) untuk mengelola konfigurasi pixel, event conversion, landing page redirect, custom domain, dan manajemen user/role.

## Highlights

- Native PHP 8.2+ + PDO + MySQL 8 (tanpa Laravel/Symfony)
- Dashboard admin/user untuk monitoring conversion
- Integrasi TikTok Events API (`/open_api/v1.3/event/track/`)
- Landing page builder dengan slug siap iklan dan copy link
- Role management (admin dapat membuat user/admin)
- Custom domain verification + mapping ke landing page

## Tech Stack

- PHP 8.2+
- MySQL 8.0
- Apache (`mod_rewrite`, `DocumentRoot` ke `public/`)
- Tailwind Play CDN (tanpa build Node)
- Docker Compose (opsional, direkomendasikan)

## Quick Start (Docker) — Recommended

```bash
cp .env.example .env
# Pastikan untuk mode Docker:
# DB_HOST=mysql
# DB_PORT=3306
# APP_URL=http://localhost:8080

docker compose up -d --build
docker compose exec app composer install
docker compose exec app php database/migrate.php
docker compose exec app php database/seed.php
```

Akses aplikasi:

- App: http://localhost:8080
- MySQL host machine: `127.0.0.1:3308`

Default admin:

- Email: `admin@tiktrack.local`
- Password: `admin123`

## Quick Start (Local / XAMPP)

```bash
cp .env.example .env
composer install
php database/migrate.php
php database/seed.php
```

Set `.env` untuk local (contoh):

- `DB_HOST=127.0.0.1`
- `DB_PORT=3306`
- `DB_NAME=tiktrack_php`
- `DB_USER=root`
- `DB_PASSWORD=...`
- `APP_URL=http://localhost/tiktrack-php/public`

Pastikan Apache `DocumentRoot` mengarah ke folder `public/`.

## Environment Variables Penting

Lihat file `.env.example`. Variabel utama:

- `APP_URL`: base URL aplikasi untuk redirect/link builder
- `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`: koneksi database
- `SAAS_BASE_DOMAINS`: daftar domain SaaS internal (comma-separated)
- `DOMAIN_VERIFY_VPS_IP`: IP target verifikasi domain
- `DOMAIN_VERIFY_LOCAL_MODE`: `true` untuk skip A-record check saat local
- `NEXT_PUBLIC_CUSTOM_DOMAIN_SCHEME`: `http` / `https` untuk format copy link domain

## Struktur Penting

- `public/index.php` — front controller
- `app/Controllers` — business logic per fitur
- `app/Views` — UI server-rendered
- `routes/web.php` — route mapping
- `database/migrations` — SQL schema
- `database/seed.php` — seed data awal

## Troubleshooting Singkat

- **Blank/fatal saat login atau dashboard**: cek log container (`docker compose logs app`) dan pastikan migrasi + seed sudah dijalankan.
- **DB connection refused di Docker**: pastikan `.env` memakai `DB_HOST=mysql` dan `DB_PORT=3306`.
- **Route tidak terbaca**: pastikan akses lewat `public/` dan `mod_rewrite` aktif.

## License

Private — PT Goami Bagjatama Indonesia.
