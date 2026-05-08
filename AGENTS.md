# TikTrack PHP

- Native PHP 8.2+ + PDO + MySQL 8. No Laravel/Symfony.
- Front controller: `public/index.php`. Apache `DocumentRoot` must point to `public/`.
- Run migrations: `php database/migrate.php` then `php database/seed.php`.
- Docker: `docker compose up -d` — app on http://localhost:8080, MySQL on 127.0.0.1:3308.
- Tailwind: Play CDN in layouts (no Node build).
