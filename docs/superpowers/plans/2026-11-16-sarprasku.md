# Sarprasku Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Aplikasi inventaris internal sekolah yang berfungsi dengan aset per unit dan perpindahan yang terlacak.
**Architecture:** Monolith, Form Request untuk validasi, Policy/Gate untuk izin, action/service untuk transaksi, Blade + Livewire untuk UI.
**Tech Stack:** PHP 8.3, Laravel 12, Fortify, Livewire, Tailwind, MySQL, Dompdf, Endroid QR.
**Spec:** `docs/design.md` dan permintaan lengkap pengguna.

## Global Constraints
- Tidak menghapus database pengguna; database development/testing terpisah.
- Satu sekolah; tidak ada pendaftaran publik; input Bahasa Indonesia.
- UUID aset, DECIMAL nominal, pengadaan final, pendanaan hanya tingkat pengadaan.
- Perubahan posisi hanya melalui persetujuan hari ini sesuai zona waktu sekolah.
- Lampiran privat dan semua baca/tulis/unduh/ekspor/cetak memerlukan izin.

## Review Focus
- Permintaan HTTP yang memalsukan actor/role/school/status ditolak/diabaikan melalui whitelist.
- Dua pengajuan unit sama ditahan oleh lock dan unique slot database.
- Nama master berubah tidak mengubah snapshot historis.
- Jumlah uang desimal dan CSV dengan whitespace/karakter kontrol tidak menimbulkan salah alokasi atau formula.
- Akun nonaktif dan sesi lama kehilangan akses, termasuk Livewire.

### Task 1: Fondasi, akun, master
Files: migrations, models, policies, middleware, Fortify provider, UserController, MasterController, tests/Feature/AccessTest.php.
Interfaces: User::hasPermission(string): bool; Gate permission names; master dengan is_active, school singleton.
- [x] Buat test akses tamu, login/logout/reset, nonaktif, role escalation, last administrator, master.
- [x] Jalankan tests (RED), implementasi fondasi dan Form Requests, jalankan sampai GREEN.

### Task 2: Pengadaan dan aset
Files: ProcurementService, AssetController, ProcurementController, requests, tests/Feature/InventoryTest.php.
Interfaces: ProcurementService::create(array, User): Procurement; aset UUID, inventaris unik, event awal.
- [x] Test unit jamak, uang tidak seimbang, duplikat, metadata palsu, data belum diketahui.
- [x] RED → migrasi/service/controller/form → GREEN.

### Task 3: Perpindahan dan perawatan
Files: TransferService, TransferController, MaintenanceController, tests/Feature/TransferTest.php.
Interfaces: draft, submit, approve, reject, cancel; append event/audit dalam transaksi.
- [x] Test seluruh transisi, self-approval, double submit/approve, konflik asal, rollback, correction, snapshot, condition/maintenance.
- [x] RED → implementasi lock/unique/transaction/validasi → GREEN.

### Task 4: Laporan, berkas, QR, label, UI
Files: report/attachment/label controllers, livewire asset table, resources/views, tests/Feature/OutputTest.php.
Interfaces: query filter bersama untuk tabel/CSV/PDF; download privat; QR token stabil.
- [x] Test permission objek/QR/lampiran/ekspor, MIME, CSV injection, filter dan PDF.
- [x] RED → UI Indonesia responsif, dashboard, timeline, file, laporan/label → GREEN.

### Task 5: Verifikasi dan dokumentasi
Files: README.md, docs/{permissions,operator,deployment,backup,testing,future-stock,references}.md.
- [x] Run seluruh test MySQL, Pint, Vite build, composer audit, npm audit.
- [x] Periksa UI desktop/ponsel, QR digital dan PDF; catat batas pemindaian fisik.
- [x] Review independen final; perbaiki temuan penting dengan test regresi.
- [x] Catat hasil aktual dan langkah penggunaan serta deployment/restore.
