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
- Photo Reports dengan upload public storage, filter tanggal, preview, edit, dan hapus.
- Master material dan ledger material flow dengan running stock, pencarian, pagination bulan, dan upload bukti.
- Cash flow dengan debit, kredit, saldo otomatis, summary, pencarian, pagination bulan, edit, dan hapus.

Semua data operasional terisolasi berdasarkan proyek. Saldo material dan kas selalu dihitung ulang berdasarkan urutan tanggal transaksi.

## Testing

```powershell
php -d extension=zip -d extension=fileinfo -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit --testdox
```
