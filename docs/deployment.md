# Deployment PHP/MySQL shared hosting

## Prasyarat dan persiapan

PHP 8.3+ dengan BCMath, GD, PDO MySQL, Fileinfo, mbstring, intl, XML/DOM, cURL, OpenSSL, ZIP; MySQL 8.0+ dengan InnoDB/foreign keys dan utf8mb4. Composer 2 tersedia saat build atau di mesin pengembang yang cocok dengan PHP hosting. Build Vite di mesin lokal/CI memakai Node LTS kompatibel, lalu upload `public/build` beserta manifest; Node tidak harus tersedia saat runtime hosting. `composer check-platform-reqs` pada PHP hosting tetap wajib.

Laravel 12.72.0 masih dalam masa dukungan keamanan hingga 24 Februari 2027. Evaluasi upgrade terencana sebelum tenggat; jangan mengganti major tanpa pengujian ulang. Paket resmi/terpelihara dan versi persis dikunci pada composer.lock/package-lock.json. Lihat references.md.

Siapkan domain/HTTPS, database serta user MySQL khusus aplikasi (bukan root), akses PHP CLI sesuai versi web, ruang disk berkas privat, SMTP dan backup privat. Tidak perlu Redis, queue worker permanen atau cron untuk fitur inti; queue berjalan sync. Kirim email reset melalui SMTP yang sudah diuji. Jangan memakai mail log/array di production karena email tidak terkirim atau token dapat tersimpan di log.

## Tata letak yang aman

Ideal: source di `/home/ACCOUNT/sarprasku`, document root domain `/home/ACCOUNT/sarprasku/public`. `.env`, vendor, database, source, storage/private dan backup berada di luar webroot. Jangan upload database MySQL development `storage/mysql-local`, `.env.testing`, `storage/qa`, cache development, node_modules atau tests ke paket production.

Jika panel tidak mengizinkan perubahan document root: source tetap `/home/ACCOUNT/sarprasku`; salin **hanya isi** folder public ke `/home/ACCOUNT/public_html`. Pada public_html/index.php sesuaikan tiga path ke source (maintenance file, vendor/autoload.php dan bootstrap/app.php). Pada bootstrap/app.php tambahkan `->withBindings` bukan cara mengatur path; gunakan pemanggilan `usePublicPath('/home/ACCOUNT/public_html')` pada instance aplikasi setelah Application::configure(...)->create(), sebelum return. Dengan demikian Vite membaca manifest dari public_html/build. Uji kembali aset dan Livewire; jangan letakkan seluruh source pada public_html atau mengandalkan `.htaccess` untuk menutup folder sensitif.

Contoh penyesuaian akhir bootstrap/app.php untuk struktur terpisah (ganti path akun dan pertahankan konfigurasi middleware/routing yang ada):

```php
$app = Application::configure(basePath: dirname(__DIR__))
    // konfigurasi routing, middleware dan exceptions proyek tetap di sini
    ->create();
$app->usePublicPath('/home/ACCOUNT/public_html');
return $app;
```

Jangan membuat `storage:link` ke lampiran privat. Disk local memakai `storage/app/private`, `serve=false`; unduh hanya melalui controller berizin. Folder `storage/app/public` tidak dipakai untuk lampiran.

## Urutan deployment

1. Backup database, lampiran, source release lama dan APP_KEY; uji rencana pemulihan. Lakukan maintenance dengan `php artisan down` selama perubahan skema/file yang saling terkait.
2. Di lokal/CI: `composer install --no-dev --prefer-dist --optimize-autoloader`, `npm ci`, `npm run build`. Pastikan public/build/manifest.json dan file hash yang dirujuk tersedia. Jangan menyalin file public/hot dari Vite development.
3. Upload source/release dan aset produksi. Buat `.env` privat, isi APP_ENV=production, APP_DEBUG=false, APP_URL=https://domain-sekolah, DB_* dan SMTP. Atur SESSION_DRIVER=database, SESSION_SECURE_COOKIE=true, SESSION_HTTP_ONLY=true, SESSION_SAME_SITE=lax, SESSION_ENCRYPT=true, QUEUE_CONNECTION=sync, LOG_LEVEL=warning. Jika memakai proxy HTTPS, konfigurasikan trusted proxies spesifik sesuai hosting; jangan percaya semua proxy tanpa alasan.
4. Buat APP_KEY **sekali** saat instalasi pertama (`php artisan key:generate`). Simpan salinan rahasianya untuk pemulihan. Jangan membuat ulang ketika update; key mempengaruhi sesi terenkripsi dan data terenkripsi lainnya.
5. `composer check-platform-reqs`, `php artisan optimize:clear`, kemudian `php artisan migrate --force`. Periksa hasil migrasi; **jangan migrate:fresh pada production**. MySQL DDL dapat implicit commit; backup tetap diperlukan meskipun kode bisnis memakai transaksi.
6. Instalasi pertama: `php artisan db:seed --class=FoundationSeeder --force`, lalu `php artisan sarpras:admin` secara interaktif. Seeder tidak membuat password/akun, tidak mereset permission yang sudah dipetakan, tidak membuat klasifikasi resmi. Jangan jalankan DemoSeeder di production; terdapat guard lingkungan.
7. `php artisan config:cache`, `php artisan route:cache`, `php artisan view:cache`. Pastikan setting env dibaca lewat file config; perubahan `.env` sesudahnya memerlukan config cache ulang.
8. Akses tulis hanya untuk user PHP pada storage dan bootstrap/cache (serta folder font cache PDF di storage jika dikustomisasi). File lainnya read-only bagi proses web. Sesuaikan pemilik/grup dan permission hosting; **jangan chmod 777**.
9. `php artisan up`; uji login/logout/reset email, pemisahan operator/penyetuju, satu pengadaan, unggahan/unduhan berizin, CSV/PDF dan label QR pada HTTPS final. Uji dari ponsel sekolah. Cek akses `.env`, vendor, storage privat dan backup harus ditolak/tidak tersedia. Jangan mengirim kredensial nyata ke layanan pengecekan publik.

Jika CLI tidak tersedia, jalankan Composer/build pada mesin yang kompatibel dan minta panel/administrator hosting mengeksekusi migrasi dan bootstrap via fasilitas CLI privat. **Jangan membuat endpoint Artisan publik**, termasuk yang memakai “secret URL”.

## Gagal deployment

Pertahankan maintenance, simpan log kegagalan tanpa membagikan rahasia, pulihkan pasangan source + database + lampiran dari backup terverifikasi jika migrasi tidak kompatibel dengan source lama. Jangan menekan migrate:rollback berulang tanpa mengetahui perubahan data. Ikuti backup.md, pertahankan APP_KEY, lalu ulang cache dan smoke test sebelum membuka akses. Jadwalkan backup privat dan audit dependensi berkala; cron panel hanya dibutuhkan jika sekolah memilih otomasi backup, bukan untuk fitur aplikasi inti.
