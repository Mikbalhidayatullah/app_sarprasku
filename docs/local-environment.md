# Lingkungan lokal sesi implementasi

Workspace awal kosong, bukan repository Git dan tidak memiliki AGENTS.md yang berlaku. Dibuat instalasi Laravel baru tanpa mengambil source proyek lain. PHP CLI 8.3.30, Composer 2.9.5, Node 25.9.0, npm 11.12.1. Untuk pemakaian berulang disarankan Node LTS kompatibel; versi host saat ini bukan target runtime hosting.

MySQL lama tidak dijalankan atau dimigrasikan. Server MySQL 8.4.3 khusus proyek dibuat pada `127.0.0.1:3308`, datadir `storage/mysql-local`, database `sarprasku_dev` dan `sarprasku_test`. Folder itu diabaikan Git dan tidak boleh diupload. Instance ini development lokal, bukan konfigurasi keamanan production; koneksi production wajib memakai akun database khusus dan rahasia environment.

Jika server lokal berhenti setelah reboot dan ingin melanjutkan instance khusus ini, jalankan dari PowerShell (jangan menjalankan initialize ulang pada datadir berisi data):

```powershell
Start-Process -FilePath 'C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqld.exe' -ArgumentList '--no-defaults','--basedir=C:/laragon/bin/mysql/mysql-8.4.3-winx64','--datadir=C:/Users/zeroTwo/Herd/app_sarprasku/storage/mysql-local','--port=3308','--bind-address=127.0.0.1','--mysqlx=OFF','--log-error=C:/Users/zeroTwo/Herd/app_sarprasku/storage/mysql-local/server.log' -WindowStyle Hidden
```

Alternatifnya gunakan instance MySQL pengembangan Anda sendiri, buat database baru dan ubah .env/.env.testing. Jangan pindahkan berkas datadir antar versi MySQL secara sembarang; gunakan dump/restore. Detail koneksi lokal tetap hanya dalam environment privat.

URL Herd yang diuji: http://app_sarprasku.test . Data CONTOH tersedia untuk pengenalan. Akun QA dibuat setelah izin eksplisit pengguna, dipakai hanya untuk pemeriksaan browser, kemudian dinonaktifkan dan sesinya dicabut. Tidak ada akun default yang dapat dipakai masuk. Buat akun Anda dengan `php artisan sarpras:admin` melalui terminal interaktif.
