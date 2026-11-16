# Hasil pengujian dan batas verifikasi

Tanggal pengujian: 16 November 2026. Database MySQL 8.4.3 terpisah `sarprasku_test`; PHP 8.3.30. Suite menolak koneksi bukan MySQL atau database tanpa akhiran `_test` sebelum migrasi testing. Tidak ada migrate:fresh pada database development atau database lama pengguna.

## Hasil aktual

| Perintah / pemeriksaan | Hasil aktual |
| --- | --- |
| `php artisan test --compact` | **72 passed, 263 assertions**, 90,77 detik pada run terakhir |
| `php vendor/bin/pint` lalu `php vendor/bin/pint --test` | Formatter dijalankan; pemeriksaan final `passed`, 0 file bermasalah |
| `npm run build` | Berhasil; Vite 6.4.6, manifest dan aset produksi dibuat |
| `composer validate --strict` | `composer.json is valid` |
| `composer audit --format=json` | Tidak ada advisory; daftar advisories kosong |
| `npm audit --json` | Total vulnerabilities 0 (info/low/moderate/high/critical seluruhnya 0) |
| `php artisan config:cache`, `route:cache`, `view:cache` | Berhasil; halaman login HTTP 200 saat cache aktif |
| `php artisan optimize:clear` | Berhasil sesudah smoke test cache; workspace dikembalikan ke konfigurasi development tanpa cache |
| PDF satu label | PDF A4 valid, satu halaman tanpa halaman kosong, render Poppler diperiksa |
| PDF label massal | 12 label pada satu A4; 13 label pada dua halaman; test otomatis memeriksa jumlah halaman |
| QR dari raster PDF 12 label | ZXing-C++ 3.1.1 mendekode **12 QR unik**, seluruh payload berbentuk URL `/q/` pada APP_URL |
| UI browser lokal | Login, dashboard, pencarian Livewire menjadi 1 hasil, detail barang dan preview label berhasil diperiksa |
| Responsif | Desktop 1440×1000 dan ponsel 390×844; menu ponsel berfungsi, tabel memiliki scroll lokal tanpa overflow halaman |
| Penutupan QA | Akun sementara nonaktif, 0 akun QA aktif, sesi database dicabut; reload browser kembali ke login; credentials.json dihapus |

Log pemeriksaan berada pada `storage/final-tests.log`, `storage/frontend-build.log`, `storage/pint-final-check.json`, `storage/composer-audit.json`, dan `storage/npm-audit.json`; semuanya file runtime yang diabaikan Git. Screenshot dan PDF/raster sintetis tersedia di `storage/qa`, juga diabaikan Git. Jangan upload direktori QA atau log pengujian ke production.

## Cakupan suite

- Tamu, login/logout, login akun nonaktif, akses sesi lama, throttling, CSRF dan redirect internal; reset melalui broker nyata, token sekali pakai dan pencabutan sesi.
- Matriks akses endpoint empat peran, eskalasi peran sendiri, izin peran administrator yang dilindungi, administrator aktif terakhir, perubahan akun dan audit.
- Akses objek luar sekolah, QR, lampiran, ekspor dan label tanpa izin; render Livewire memeriksa izin dan escape nama berbahaya.
- Nomor inventaris duplikat, pembentukan unit jamak, uang desimal persis, alokasi tidak seimbang, rollback pengadaan ketika unit bertabrakan, data awal NULL, whitelist actor/school/condition/status/posisi.
- Draft/pengajuan tidak mengubah posisi; persetujuan sekali; penolakan/pembatalan; larangan self-approval; konflik asal; hari efektif; slot aktif unik; koreksi dan snapshot master.
- Kegagalan foreign key saat menulis histori mem-rollback posisi serta keputusan. Pengujian tidak memakai trigger DDL di tengah transaksi karena implicit commit MySQL akan merusak setup test.
- **Dua proses PHP dengan koneksi MySQL independen** ditahan pada row lock yang sama; `performance_schema.data_lock_waits` membuktikan keduanya menunggu. Sesudah lock dilepas, tepat satu diterima dan satu konflik pada draft maupun persetujuan. Posisi dan event persetujuan tetap tunggal.
- MIME/ekstensi unggahan, penolakan SVG/HTML, private storage, unduhan berizin, akses langsung storage ditolak; metadata dokumen ikut memerlukan permission baca.
- Perubahan kondisi, pemeliharaan, arsip versus pelepasan dengan dokumen, laporan berfilter, CSV formula/control characters dan nol awal.
- Tahun dari tanggal perolehan, penolakan tahun bertentangan, current inactive master tetap tersedia saat edit, nama pelaku historis konsisten setelah rename akun, dan tanggal efektif memakai Asia/Jayapura alih-alih mengasumsikan WIB.
- Label tidak memotong inventaris/NUP; layout terlalu kecil ditolak; preview dan keluaran PDF, jumlah halaman satuan/massal dan tidak ada halaman kosong tambahan.

## Hasil review independen

Reviewer read-only tidak menemukan Critical. Tiga Important diperbaiki dengan test regresi: tahun perolehan dari tanggal, pilihan master lama yang nonaktif pada edit, dan snapshot pelaku historis. Temuan metadata lampiran dinilai perlu diperbaiki karena judul dokumen juga dapat bersifat privat; listing kini dilindungi permission. Tidak ada temuan minor yang ditangguhkan.

Pemeriksaan visual PDF menemukan bug tambahan yang tidak tertangkap test header PDF: Dompdf default media screen mengambil ukuran halaman preview dan menambah halaman kosong. Perbaikan memakai media print. Uji massal kemudian mengungkap ukuran content-box Dompdf; padding/border kini dihitung agar label fisik sesuai ukuran. Keduanya mempunyai regresi yang lebih dahulu gagal lalu lulus.

## Batas yang belum diverifikasi / keputusan tahap ini

- **Belum ada cetak/pemindaian fisik.** Decode digital dari raster PDF tidak membuktikan mutu printer, kertas, tinta, kamera, jarak atau jaringan sekolah. Lakukan uji cetak 100% dan pemindaian ponsel sebelum produksi massal.
- Deployment hosting nyata, HTTPS/cookie hosting, konfigurasi SMTP/delivery email, backup/restore production dan kebijakan retensi sekolah belum dijalankan. Fitur reset diuji dengan broker nyata, bukan pengiriman email eksternal. Browser IAB sempat timeout pada penantian event unduhan; keluaran PDF server diverifikasi melalui HTTP test dan render lokal, bukan keberhasilan dialog unduhan IAB.
- NUP dan kode klasifikasi resmi memerlukan referensi sekolah. Tidak ada klaim laporan resmi BMN; cakupan unik NUP belum ditetapkan dan belum dipaksakan sebagai unique global. Nomor inventaris unik dalam sekolah.
- Perpindahan, pemeriksaan kondisi dan pemeliharaan tahap ini hanya hari ini menurut zona waktu sekolah. Histori lama posisi melalui pencatatan awal eksplisit; tidak ada rekonstruksi mundur atau penjadwalan.
- Pengadaan final tidak memiliki UI pembalik/koreksi nominal. Metadata perolehan aset terkait dikunci. Transaksi koreksi perpindahan sudah tersedia; koreksi pengadaan administratif perlu rancangan khusus sebelum ditambahkan.
- Pendanaan hanya tingkat pengadaan. Tidak ada pembagian nominal dana per unit; laporan tidak menduplikasi total pengadaan.
- Scope instalasi satu sekolah. Tidak ada pembatasan akun per ruangan, SaaS, stok habis pakai atau siswa. Pilihan master pada formulir dimuat sebagai daftar referensi; data aset/transaksi menggunakan pagination server-side.
- Batas label 100 unit/batch, PDF laporan 1.000 baris, pengadaan 1.000 unit/transaksi. Sesuaikan beban setelah mengukur hosting nyata; gunakan CSV untuk laporan lebih besar.
- Unggahan dibatasi format, header/gambar, ukuran dan disimpan privat; antivirus/CDR belum diintegrasikan. Audit UI bersifat append-only tetapi administrator DB tetap bisa mengubah data. Audit bukan bukti kebal manipulasi.
- Audit dependensi adalah snapshot pemeriksaan saat ini, bukan jaminan bebas kerentanan ke depan. Jadwalkan pemeriksaan ulang dan rencanakan upgrade sebelum 24 Februari 2027.

## Ringkasan enam tahap

| Tahap | Hasil berfungsi | Verifikasi | Sisa konkret |
| --- | --- | --- | --- |
| 1. Pemeriksaan/desain | Lingkungan diperiksa, desain, ERD dan rencana tersimpan | docs/design.md dan implementation-log.md | Workspace belum Git; tidak ada histori commit |
| 2. Akun/izin/master | Fortify, empat peran, pemetaan izin, akun nonaktif, master Indonesia | AccessTest, SecurityTest, ListTest | SMTP hosting belum tersedia |
| 3. Pengadaan/aset | Unit UUID, identitas terpisah, pendanaan tepat, data awal NULL | InventoryTest dan RegressionTest | Referensi klasifikasi/NUP milik sekolah |
| 4. Histori/perpindahan | Locking, keputusan, koreksi, timeline, kondisi/perawatan | TransferTest dan MySqlConcurrencyTest | Rekonstruksi tanggal lama ditunda |
| 5. Dashboard/laporan/QR | Filter server, CSV/PDF, lampiran privat, QR dan label | OutputTest, browser, Poppler, decode 12 QR | Uji cetak fisik belum dilakukan |
| 6. Verifikasi/hosting | 72 test, formatter, build, audit, panduan deployment/backup | Hasil aktual di atas | Deployment dan restore hosting nyata belum dilakukan |
