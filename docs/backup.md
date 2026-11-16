# Backup dan pemulihan

Backup harus mencakup database, `storage/app/private`, konfigurasi `.env`/APP_KEY yang dienkripsi, dan identitas release/source+lockfile+public/build. Database saja tidak memulihkan berkas pendukung; lampiran saja tidak memulihkan keterkaitan objek. Jangan menaruh backup pada public atau URL unduhan publik.

## Prosedur backup

1. Pilih jendela maintenance agar penulisan database dan upload berhenti. Catat waktu sekolah, release, schema migration terakhir, jumlah aset/lampiran dan checksum manifest.
2. Jalankan dump dari terminal/panel privat; password diminta interaktif, tidak diletakkan pada argumen/log:

```sh
mysqldump --host=HOST --user=BACKUP_USER --password --single-transaction --quick --no-tablespaces --routines --triggers NAMA_DATABASE > /PRIVATE_BACKUP/database.sql
```

3. Arsipkan lampiran privat dan konfigurasi/release di lokasi backup privat, enkripsi sebelum penyimpanan offsite. Jangan memasukkan password ke nama berkas. Sertakan checksum SHA-256 dump/arsip serta jumlah berkas untuk pemeriksaan integritas. Akses backup dibatasi administrator yang ditugaskan, bukan seluruh operator.
4. Buka maintenance setelah pasangan dump dan lampiran lengkap. Contoh retensi yang perlu disesuaikan kebijakan sekolah: harian 7 hari, mingguan 4 minggu, bulanan 12 bulan; minimal satu salinan terenkripsi di media/lokasi berbeda. Uji restore paling sedikit tiap perubahan besar dan secara berkala. Retensi bukan klaim aturan BMN resmi.

## Restore uji sebelum production

1. Buat database pemulihan baru dan direktori privat terpisah. Jangan menimpa database operasional. Gunakan release yang sesuai backup; nonaktifkan pengiriman email keluar pada lingkungan uji restore.
2. Restore dump melalui panel atau CLI privat:

```sh
mysql --host=HOST --user=RESTORE_USER --password NAMA_DATABASE_RESTORE < /PRIVATE_BACKUP/database.sql
```

3. Pulihkan lampiran dengan struktur/nama acak asli, checksum dan hak akses privat; set konfigurasi koneksi ke database restore. Pertahankan APP_KEY asli untuk data terenkripsi. URL restore berbeda hanya untuk pengujian, jangan mencetak ulang label massal dari domain sementara.
4. Bandingkan jumlah aset, pengadaan, alokasi, transaksi perpindahan, audit dan lampiran. Pilih sampel unit: baca timeline, cek posisi terkini, unduh gambar/PDF, cetak label uji, uji role dan akun nonaktif. Pastikan jumlah/nominal pendanaan konsisten dan berkas sampel cocok checksum. Simpan bukti hasil/tanggal/penguji. Backup yang belum pernah di-restore belum terbukti dapat dipulihkan.
5. Untuk pemulihan production yang telah disetujui sekolah: maintenance, backup keadaan saat ini, alihkan database/lampiran/source sebagai satu pasangan, perbarui cache konfigurasi/routes/views, lakukan smoke test lalu buka akses. Jangan ganti APP_KEY. Bila pengujian gagal, kembalikan pasangan sebelumnya dan pertahankan maintenance sampai akar masalah ditemukan.

Backup/restore production dan job retensi otomatis belum dijalankan pada hosting nyata dalam implementasi ini.
