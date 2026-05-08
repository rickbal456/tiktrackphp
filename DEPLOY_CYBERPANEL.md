# Deploy TikTrack PHP ke VPS (CyberPanel)

Panduan ini untuk deploy project `tiktrack-php` ke VPS dengan CyberPanel + OpenLiteSpeed secara rapi dan aman.

## 1) Prasyarat

- VPS Linux aktif + akses SSH root/sudo
- CyberPanel sudah terpasang
- Domain sudah mengarah ke IP VPS (A record)
- Git, PHP 8.2+, Composer, dan MySQL 8.x tersedia di server

## 2) Buat Website di CyberPanel

1. Login ke CyberPanel.
2. Buka **Websites > Create Website**.
3. Isi:
   - **Package**: default package
   - **Domain Name**: domain utama (contoh `app.domainkamu.com`)
   - **Email**: email admin
   - **PHP**: pilih `8.2` atau lebih tinggi
4. Klik **Create Website**.

## 3) Siapkan Document Root ke folder `public`

Project ini wajib expose `public/index.php` sebagai front controller.

### Opsi A (disarankan): symlink `public_html` -> `public`

Masuk SSH, lalu:

```bash
cd /home/domainkamu.com
rm -rf public_html
mkdir -p app
cd app
git clone https://github.com/<username>/tiktrackphp.git .
ln -s /home/domainkamu.com/app/public /home/domainkamu.com/public_html
```

### Opsi B: ubah vhost root ke `/home/domainkamu.com/app/public`

Atur dari CyberPanel/OpenLiteSpeed vHost config jika kamu nyaman edit konfigurasi web server.

## 4) Deploy source code

Jika repo private, gunakan salah satu:

- Deploy key SSH di GitHub
- Personal Access Token saat clone

Clone project (contoh):

```bash
cd /home/domainkamu.com/app
git clone https://github.com/rickbal456/tiktrackphp.git .
```

## 5) Buat file `.env`

```bash
cd /home/domainkamu.com/app
cp .env.example .env
```

Contoh minimal `.env` production:

```env
APP_NAME=TikTrack
APP_URL=https://app.domainkamu.com

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=tiktrack_php
DB_USER=tiktrack_user
DB_PASSWORD=GANTI_PASSWORD_KUAT

SAAS_BASE_DOMAINS=app.domainkamu.com
DOMAIN_VERIFY_VPS_IP=<IP_VPS_KAMU>
DOMAIN_VERIFY_LOCAL_MODE=false
NEXT_PUBLIC_DOMAIN_VERIFY_LOCAL_MODE=false
NEXT_PUBLIC_VPS_IP=<IP_VPS_KAMU>
NEXT_PUBLIC_CUSTOM_DOMAIN_SCHEME=https
```

## 6) Siapkan database (MySQL)

Buat database/user MySQL dari CyberPanel (Database > Create Database) atau via SQL:

```sql
CREATE DATABASE tiktrack_php CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'tiktrack_user'@'localhost' IDENTIFIED BY 'GANTI_PASSWORD_KUAT';
GRANT ALL PRIVILEGES ON tiktrack_php.* TO 'tiktrack_user'@'localhost';
FLUSH PRIVILEGES;
```

## 7) Install dependency + migrasi

```bash
cd /home/domainkamu.com/app
composer install --no-dev --optimize-autoloader
php database/migrate.php
php database/seed.php
```

Default akun admin:

- Email: `admin@tiktrack.local`
- Password: `admin123`

Setelah login pertama, segera ubah password admin.

## 8) Permission file/folder

Sesuaikan owner web user CyberPanel (umumnya `nobody` atau user domain):

```bash
chown -R nobody:nobody /home/domainkamu.com/app
find /home/domainkamu.com/app -type f -exec chmod 644 {} \;
find /home/domainkamu.com/app -type d -exec chmod 755 {} \;
```

> Jika server kamu memakai user/group berbeda, sesuaikan nilainya.

## 9) Aktifkan SSL (Let's Encrypt)

1. CyberPanel > **Websites > List Websites**
2. Pilih domain > **Manage**
3. Klik **Issue SSL**
4. Pastikan `APP_URL` di `.env` memakai `https://...`

## 10) Restart service

```bash
systemctl restart lsws
```

Jika perlu restart PHP handler juga dari panel/server.

## 11) Verifikasi akhir

- Buka `https://app.domainkamu.com`
- Login admin
- Cek menu: Dashboard, TikTok Config, Landing Pages, Conversions
- Tes buat user dari Role Management
- Tes copy link LP dan akses link LP

## Update Deployment (setiap ada perubahan)

```bash
cd /home/domainkamu.com/app
git pull origin main
composer install --no-dev --optimize-autoloader
php database/migrate.php
systemctl restart lsws
```

## Troubleshooting cepat

- **404 / route tidak jalan**: pastikan domain mengarah ke folder `public`.
- **DB connection refused**: cek `.env` DB_* dan kredensial database.
- **403 Forbidden**: cek owner/permission folder project.
- **SSL tidak keluar**: cek DNS sudah mengarah benar ke IP VPS.

---

Jika kamu mau, saya bisa lanjut bikinkan versi kedua khusus **deployment via Docker di CyberPanel** (dengan reverse proxy ke container app).
