# Sarprasku

Aplikasi inventaris internal sekolah: Laravel 12, PHP 8.3+, MySQL, Blade, Livewire 4 dan Tailwind CSS. Satu record aset adalah satu unit fisik. Aplikasi ini **bukan pengganti sistem resmi pemerintah atau pernyataan kepatuhan penuh BMN**.

## Menjalankan lokal

Prasyarat: PHP 8.3+ dengan BCMath, PDO MySQL, GD, Fileinfo, mbstring, intl, XML/DOM, cURL, OpenSSL dan ZIP; Composer 2; MySQL 8.0+ (diuji 8.4.3); Node 22.12+ atau versi LTS kompatibel dengan Vite; npm. Lockfile disertakan.

```sh
composer install
cp .env.example .env
```

Pada Windows PowerShell gunakan `Copy-Item .env.example .env`. Buat database kosong dan akun MySQL khusus aplikasi. Isi DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD serta APP_URL dalam `.env`. Jangan gunakan database operasional aplikasi lain. Atur SMTP untuk reset password; untuk lokal dapat memakai mail catcher privat.

```sh
php artisan key:generate
php artisan migrate
php artisan db:seed --class=FoundationSeeder
php artisan sarpras:admin
npm ci
npm run build
php artisan serve
```

Buka URL yang dicetak `artisan serve`, atau gunakan Herd dengan document root `public` dan `APP_URL=http://app_sarprasku.test`. `sarpras:admin` meminta nama, email, password dan konfirmasi melalui prompt tersembunyi; password tidak diterima sebagai argumen atau dicetak. Tidak ada akun/password default dan tidak ada pendaftaran publik.

Login, lengkapi identitas sekolah/zona waktu, masukkan master dari referensi sekolah, lalu buat akun operator dan penyetuju yang berbeda. Akun penanggung jawab barang tidak wajib memiliki login. Jalankan `composer dev` jika ingin server PHP dan Vite bersama; tidak membutuhkan Redis/worker permanen.

## Fitur yang tersedia

- Login/logout/reset password resmi Fortify, pembatasan percobaan, akun nonaktif dan pencabutan sesi; peran/permission, perlindungan administrator terakhir.
- Master sekolah, lokasi, ruangan, pegawai, klasifikasi teks, sumber dana dan kepemilikan eksplisit.
- Inventaris UUID per unit; nomor inventaris unik dalam sekolah; NUP, klasifikasi dan nomor seri terpisah; data awal tidak diketahui tetap kosong.
- Pengadaan banyak rincian, pembentukan unit, pendanaan majemuk dan rekonsiliasi DECIMAL/BCMath.
- Draft → pengajuan → persetujuan/penolakan/pembatalan perpindahan; koreksi merujuk transaksi lama; locking dan unique slot MySQL.
- Timeline bisnis, kondisi, pemeliharaan, status internal, arsip, dokumen/foto privat, dan audit teknis.
- Dashboard, filter/pagination server, laporan CSV/PDF, QR token stabil, preview dan label A4 satuan/massal.

Data contoh opsional, **hanya local/testing**, setelah administrator dibuat:

```sh
php artisan db:seed --class=DemoSeeder
```

Semua contoh memakai penanda CONTOH/DEMO, tidak mengarang kode klasifikasi/NUP resmi, dan tidak membuat akun. Database development sesi implementasi ada pada port 3308 (`sarprasku_dev`), terpisah dari MySQL lama; lihat [lingkungan lokal](docs/local-environment.md).

## Pengujian

Buat database MySQL kosong **berakhiran `_test`** dan akun MySQL testing yang boleh membuat/menghapus tabel di database tersebut saja. Salin `.env.testing.example` ke `.env.testing`, isi koneksi, lalu:

```sh
php artisan key:generate --env=testing
php artisan test
php vendor/bin/pint --test
npm run build
composer validate --strict
composer audit
npm audit
```

Suite menolak database non-MySQL atau nama tanpa `_test` sebelum trait migrasi berjalan. Test concurrency memakai dua proses PHP independen, `performance_schema.data_lock_waits`, dan membutuhkan izin membacanya pada server testing. Jangan menjalankan dua suite bersamaan terhadap database testing yang sama. Suite memakai migrasi destruktif **hanya pada database testing**.

## Dokumentasi

- [ERD dan keputusan desain](docs/design.md)
- [Matriks hak akses](docs/permissions.md)
- [Panduan operator](docs/operator.md)
- [Deployment shared hosting](docs/deployment.md)
- [Backup dan restore](docs/backup.md)
- [Hasil pengujian dan keterbatasan](docs/testing.md)
- [Referensi versi/API](docs/references.md)
- [Pengembangan persediaan nanti](docs/future-stock.md)

Tidak ada endpoint publik untuk Artisan, migrasi, seeding, atau backup. Deployment hosting dan uji cetak/pemindaian fisik belum dilakukan. Rencanakan evaluasi upgrade sebelum dukungan keamanan Laravel 12 berakhir 24 Februari 2027.
