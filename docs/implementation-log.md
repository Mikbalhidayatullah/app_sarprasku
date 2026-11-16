# Catatan pelaksanaan

- Pemeriksaan: workspace kosong, bukan Git, tidak ada AGENTS.md yang berlaku. PHP 8.3.30, Composer 2.9.5, Node 25.9.0, npm 11.12.1; ekstensi pdo_mysql/bcmath/gd/fileinfo tersedia.
- Ruling: pelaksanaan langsung di workspace karena proyek baru dan pengguna meminta implementasi langsung. Tidak membuat worktree tanpa repository.
- Ruling: pilihan teknis rutin dan desain dikerjakan tanpa gate persetujuan tambahan sesuai instruksi pengguna; layanan Sites tidak dipakai untuk deployment karena target PHP/MySQL shared hosting.
- Ruling: pendanaan hanya tingkat pengadaan; tidak ada alokasi unit ganda. Biaya bila kebutuhan berubah: perlu rancangan rekonsiliasi unit terpisah.
- Ruling: kondisi dan pemeliharaan dibatasi hari ini untuk menjaga kondisi terkini tanpa rekonstruksi histori. Biaya: pencatatan kegiatan lama perlu pengembangan mekanisme histori terpisah.
- Ruling: tidak membuat unique global NUP tanpa referensi cakupan institusi resmi; inventaris unik per sekolah. Biaya: duplikat register resmi harus diverifikasi operator sampai referensi tersedia.
- Ruling: ruang kerja bukan Git; implementasi langsung tanpa commit/worktree. Biaya: tidak ada histori versi otomatis sampai pengguna menginisialisasi Git.
- Tahap 1 selesai: pemeriksaan, desain, ERD dan rencana tertulis. Pilihan teknis rutin dijalankan sesuai mandat pengguna.
- Tahap 2 selesai: akun/master/permission, perlindungan administrator terakhir, akses nonaktif/sesi, reset resmi, CSRF dan matriks endpoint diuji.
- Tahap 3 selesai: unit jamak, DECIMAL/BCMath, pendanaan dan rollback pengadaan diuji.
- Tahap 4 selesai: draft/pengajuan/keputusan/koreksi dan rollback histori; dua proses MySQL independen membuktikan locking.
- Tahap 5 selesai: dashboard, laporan, CSV/PDF, QR, preview, unggahan privat; UI desktop/ponsel dan decode digital label diperiksa.
- Final review independen read-only: tidak ada Critical; tahun perolehan, current inactive references, snapshot pelaku dan metadata lampiran diperbaiki dengan regresi RED→GREEN. Tidak ada minor ditangguhkan.
- Pemeriksaan visual tambahan: PDF media screen menghasilkan halaman kosong; diubah print. Padding/border label diperhitungkan untuk content-box Dompdf. Regresi satu/12/13 label lulus; 12 QR pada raster PDF berhasil didekode unik.
- Tahap 6 selesai: php artisan test --compact → 72 passed (263 assertions); Pint --test passed; Vite build berhasil; Composer validate valid; Composer/npm audit 0 advisory/kerentanan; config/route/view cache dan optimize:clear berhasil.
- Akun QA ditolak auto-review pada upaya awal; setelah pengguna mengizinkan secara eksplisit, akun hanya dibuat pada sarprasku_dev, digunakan untuk UI, lalu dinonaktifkan dan sesi dicabut. Kredensial serta skrip sementara dihapus. Nama pelaku tetap terhubung ke data CONTOH.
- Hosting nyata, SMTP eksternal, restore production dan pemindaian cetak fisik belum diverifikasi; batas rinci di docs/testing.md.
