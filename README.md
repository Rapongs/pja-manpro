# RuangProyek

MVP aplikasi manajemen proyek konstruksi berbasis Laravel, Blade, Tailwind CSS, dan SQLite.

## Setup lokal

Pastikan PHP memiliki ekstensi `pdo_sqlite`, `sqlite3`, `fileinfo`, dan `zip` aktif.

```powershell
composer install
npm install
Copy-Item .env.example .env
php artisan key:generate
New-Item database/database.sqlite -ItemType File -Force
php artisan migrate:fresh --seed
php artisan storage:link
npm run dev
```

Pada lingkungan PHP portable yang ekstensi-nya belum aktif, jalankan perintah Artisan dengan flag berikut:

```powershell
php -d extension=zip -d extension=fileinfo -d extension=pdo_sqlite -d extension=sqlite3 artisan migrate:fresh --seed
```

Server aplikasi lokal:

```powershell
php -d extension=zip -d extension=fileinfo -d extension=pdo_sqlite -d extension=sqlite3 -d upload_max_filesize=25M -d post_max_size=32M -S 127.0.0.1:8000 -t public public/index.php
```

Buka `http://127.0.0.1:8000`.

## Akun demo

Guest Login tersedia langsung dari halaman login. Akun manual:

- Email: `guest@ruangproyek.test`
- Password: `guest-demo-password`

## Modul

- Daftar proyek dengan search, sort, CRUD, dan dashboard metrik.
- Kurva S dengan target planned dan aktual Lapjusik.
- CRUD Lapjusik serta target Kurva S.
- Photo Reports dengan upload storage terkonfigurasi (lokal/S3), filter tanggal, preview, edit, dan hapus.
- Master material dan ledger material flow dengan running stock, pencarian, pagination bulan, dan upload bukti.
- Cash flow dengan debit, kredit, saldo otomatis, summary, pencarian, pagination bulan, edit, dan hapus.

Semua data operasional terisolasi berdasarkan proyek. Saldo material dan kas selalu dihitung ulang berdasarkan urutan tanggal transaksi.

## Testing

```powershell
php -d extension=zip -d extension=fileinfo -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit --testdox
```

## Deploy (MariaDB + AWS S3)

Kode sudah siap remote: disk foto/impor Excel dipilih lewat env (`PHOTOS_DISK`, `IMPORTS_DISK`;
lokal `public`/`local`, deploy `s3`), dan file Excel selalu dibaca lewat file temp lokal
karena `PharData` butuh path lokal.

```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env   # lalu isi: APP_ENV=production, APP_DEBUG=false, APP_URL=https://..., APP_KEY baru
```

1. **Database** — isi `.env`:
   `DB_CONNECTION=mariadb`, `DB_HOST`, `DB_PORT=3306`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.
   Lalu:
   ```bash
   php artisan migrate --force
   php artisan db:import-sqlite /path/ke/database_reset.sqlite --truncate
   ```
   Tabel `sessions`, `cache`, `cache_locks`, `jobs` tidak ikut disalin secara default
   (dibuat fresh oleh migrate). Cek daftar: `php artisan db:import-sqlite --help`.
2. **S3** — install driver sudah termasuk (`league/flysystem-aws-s3-v3`). Isi `.env`:
   `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET`
   (opsional `AWS_URL` untuk CloudFront), lalu `PHOTOS_DISK=s3`, `IMPORTS_DISK=s3`.
   Opsional logo: `LOGO_URL=https://.../logo-pt.jpeg`.
3. **Pindah file lama ke S3** (atau `aws s3 sync` manual):
   ```bash
   php artisan storage:sync-to-remote --dry-run
   php artisan storage:sync-to-remote
   ```
   Menyalin `photo-reports/*` + `logo-pt.jpeg` ke disk foto dan `progress-imports/*` ke disk impor.
4. **Server**: atur `upload_max_filesize`/`post_max_size` di php.ini/php-fpm pool
   (`.user.ini` tidak selalu dibaca FPM; `artisan serve` mengabaikannya), mis. 64M.
   Aplikasi tetap enforce maksimal 10 MB per foto.
5. **Verifikasi**: login, upload foto, impor Excel + live view, lalu `php artisan test`.
