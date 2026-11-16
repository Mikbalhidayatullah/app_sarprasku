# Referensi teknis

Diperiksa 16 November 2026. API juga dicocokkan dengan source versi yang terpasang pada vendor dan lockfile.

- Laravel 12 support policy / PHP compatibility: https://laravel.com/framework/docs/12.x/releases — security fixes sampai 24 Februari 2027.
- Laravel Fortify login, reset, konfigurasi fitur: https://laravel.com/framework/docs/12.x/fortify — hanya fitur reset diaktifkan; registration/passkey/two-factor tidak diaktifkan dalam tahap ini.
- Laravel authorization: https://laravel.com/docs/12.x/authorization — Gate dan policy objek, tanpa global administrator bypass.
- Laravel database/pessimistic locking: https://laravel.com/docs/12.x/queries#pessimistic-locking — lockForUpdate dalam DB transaction.
- Laravel file storage: https://laravel.com/framework/docs/12.x/filesystem — private local disk dan controller download; serve=false.
- Livewire 4 components/pagination/URL: https://livewire.laravel.com/docs/4.x/components ; https://livewire.laravel.com/docs/4.x/pagination ; https://livewire.laravel.com/docs/4.x/url . Composer memilih Livewire 4.4.8; dokumentasi 4.x dipakai setelah resolusi versi.
- PDF wrapper Dompdf: https://github.com/barryvdh/laravel-dompdf — loadView, setPaper dan disable remote resources/PHP.
- QR lokal Endroid: https://github.com/endroid/qr-code — QrCode + PngWriter, margin/quiet zone dan URL token. Composer memilih 6.0.9 yang kompatibel PHP 8.3, bukan 6.2 yang membutuhkan PHP 8.5.

Versi implementasi: Laravel 12.72.0, Fortify 1.42.0, Livewire 4.4.8, laravel-dompdf 3.1.2/Dompdf 3.1.6, Endroid QR 6.0.9, Vite 6.4.6. Lockfile adalah sumber versi persis untuk instalasi ulang.

Tidak ada format, kode klasifikasi, aturan NUP atau prosedur resmi BMN yang dibuat oleh aplikasi. Sekolah perlu menyediakan referensi klasifikasi yang berlaku, identitas institusi/register, dokumen perolehan/kepemilikan dan dasar prosedur koreksi/pelepasan sebelum integrasi atau klaim format resmi dipertimbangkan.
